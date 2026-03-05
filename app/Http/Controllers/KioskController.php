<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class KioskController extends Controller
{
    public function index()
    {
        return Inertia::render('Kiosk/Index');
    }

    public function templates()
    {
        $employees = Employee::whereNotNull('fingerprint_1')
            ->orWhereNotNull('fingerprint_2')
            ->get(['id', 'fingerprint_1', 'fingerprint_2']);

        $templates = [];
        foreach ($employees as $emp) {
            if ($emp->fingerprint_1) {
                $templates[] = ['employee_id' => $emp->id, 'finger' => 1, 'template' => $emp->fingerprint_1];
            }
            if ($emp->fingerprint_2) {
                $templates[] = ['employee_id' => $emp->id, 'finger' => 2, 'template' => $emp->fingerprint_2];
            }
        }

        return response()->json(['templates' => $templates]);
    }

    public function log(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'action'      => 'required|in:time_in,time_out,break1_out,break1_in,break2_out,break2_in,break3_out,break3_in',
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $action   = $request->action;
        $today    = today();
        $now      = now();

        $log = AttendanceLog::firstOrCreate(
            ['id_num' => $employee->id_num, 'date_today' => $today],
            ['status' => 'absent']
        );

        // ── Validate ──────────────────────────────────────────────
        switch ($action) {
            case 'time_in':
                if ($log->time_in)
                    return $this->blocked($employee, $log, 'Already timed in today.');
                break;

            case 'time_out':
                if (!$log->time_in)
                    return $this->blocked($employee, $log, 'Please time in first.');
                if ($log->time_out)
                    return $this->blocked($employee, $log, 'Already timed out today.');
                if ($this->isOnBreak($log))
                    return $this->blocked($employee, $log, 'Please return from break before timing out.');
                break;

            case 'break1_out':
            case 'break2_out':
            case 'break3_out':
                if (!$log->time_in)
                    return $this->blocked($employee, $log, 'Please time in first.');
                if ($log->time_out)
                    return $this->blocked($employee, $log, 'Already timed out. No further actions allowed.');
                if ($log->$action)
                    return $this->blocked($employee, $log, 'Already logged ' . $action . '.');
                if ($this->isOnBreak($log))
                    return $this->blocked($employee, $log, 'Please return from your current break first.');
                break;

            case 'break1_in':
            case 'break2_in':
            case 'break3_in':
                $outCol = str_replace('_in', '_out', $action);
                if ($log->time_out)
                    return $this->blocked($employee, $log, 'Already timed out. No further actions allowed.');
                if (!$log->$outCol)
                    return $this->blocked($employee, $log, 'Please log ' . str_replace('_', ' ', $outCol) . ' first.');
                if ($log->$action)
                    return $this->blocked($employee, $log, 'Already logged ' . $action . '.');
                break;
        }

        // ── Save ──────────────────────────────────────────────────
        switch ($action) {
            case 'time_in':
                $log->time_in = $now;
                $log->status  = $this->resolveStatus($employee, $now);
                break;
            case 'time_out':
                $log->time_out = $now;
                break;
            default:
                $log->$action = $now;
                break;
        }

        $log->save();
        $log->refresh();

        return response()->json([
            'success'         => true,
            'action'          => $action,
            'logged_at'       => $now->format('h:i:s A'),
            'full_name'       => $employee->full_name,
            'position'        => $employee->position,
            'store_dept'      => $employee->store_dept,
            'shift_from'      => substr($employee->shift_from ?? '', 0, 5),
            'shift_to'        => substr($employee->shift_to ?? '', 0, 5),
            'status_type'     => 'success',
            'allowed_actions' => $this->getAllowedActions($log),
        ]);
    }

    public function todayLogs()
    {
        $logs = AttendanceLog::with('employee')
            ->where('date_today', today())
            ->latest()
            ->get();

        $entries = [];
        $actions = ['time_in','break1_out','break1_in','break2_out','break2_in','break3_out','break3_in','time_out'];

        foreach ($logs as $log) {
            $name = $log->employee?->full_name ?? $log->id_num;
            foreach ($actions as $action) {
                if ($log->$action) {
                    $entries[] = [
                        'id'         => $log->id . '_' . $action,
                        'full_name'  => $name,
                        'position'   => $log->employee?->position ?? '—',
                        'store_dept' => $log->employee?->store_dept ?? '—',
                        'action'     => $action,
                        'time'       => Carbon::parse($log->$action)
                                            ->setTimezone(config('app.timezone'))
                                            ->format('h:i A'),
                    ];
                }
            }
        }

        usort($entries, fn($a, $b) => strcmp($b['time'], $a['time']));

        return response()->json(['logs' => $entries]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function blocked(Employee $employee, AttendanceLog $log, string $message)
    {
        return response()->json([
            'success'         => false,
            'message'         => $message,
            'full_name'       => $employee->full_name,
            'position'        => $employee->position,
            'store_dept'      => $employee->store_dept,
            'status_type'     => 'error',
            'allowed_actions' => $this->getAllowedActions($log),
        ]);
    }

    private function isOnBreak(AttendanceLog $log): bool
    {
        return ($log->break1_out && !$log->break1_in)
            || ($log->break2_out && !$log->break2_in)
            || ($log->break3_out && !$log->break3_in);
    }

    private function getAllowedActions(AttendanceLog $log): array
    {
        if ($log->time_out)  return [];
        if (!$log->time_in)  return ['time_in'];

        $allowed = [];
        $onBreak = $this->isOnBreak($log);

        if (!$onBreak) $allowed[] = 'time_out';

        if (!$onBreak) {
            if (!$log->break1_out) $allowed[] = 'break1_out';
            if (!$log->break2_out) $allowed[] = 'break2_out';
            if (!$log->break3_out) $allowed[] = 'break3_out';
        }

        if ($log->break1_out && !$log->break1_in) $allowed[] = 'break1_in';
        if ($log->break2_out && !$log->break2_in) $allowed[] = 'break2_in';
        if ($log->break3_out && !$log->break3_in) $allowed[] = 'break3_in';

        return $allowed;
    }

    private function resolveStatus(Employee $emp, Carbon $now): string
    {
        if (!$emp->shift_from) return 'present';
        $shiftStart = Carbon::createFromTimeString($emp->shift_from);
        return $now->gt($shiftStart->copy()->addMinutes(15)) ? 'late' : 'present';
    }
}