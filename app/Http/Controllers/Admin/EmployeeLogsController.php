<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class EmployeeLogsController extends Controller
{
    public function index(Request $request, Employee $employee)
    {
        $query = AttendanceLog::where('id_num', $employee->id_num);

        if ($request->from) {
            $query->whereDate('date_today', '>=', Carbon::parse($request->from));
        }
        if ($request->to) {
            $query->whereDate('date_today', '<=', Carbon::parse($request->to));
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $logs = $query->orderBy('date_today', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($log) => $this->formatLog($log));

        $allLogs = AttendanceLog::where('id_num', $employee->id_num)->get();
        $summary = [
            'total'   => $allLogs->count(),
            'present' => $allLogs->where('status', 'present')->count(),
            'late'    => $allLogs->where('status', 'late')->count(),
            'absent'  => $allLogs->where('status', 'absent')->count(),
        ];

        return Inertia::render('Admin/Employees/EmployeeLogs', [
            'employee' => [
                'id'                => $employee->id,
                'id_num'            => $employee->id_num,
                'full_name'         => $employee->full_name,
                'position'          => $employee->position,
                'store_dept'        => $employee->store_dept,
                'shift_from'        => $employee->shift_from ? Carbon::createFromTimeString($employee->shift_from)->format('g:i A') : null,
                'shift_to'          => $employee->shift_to   ? Carbon::createFromTimeString($employee->shift_to)->format('g:i A')   : null,
                'has_fp1'           => !empty($employee->fingerprint_1),
                'has_fp2'           => !empty($employee->fingerprint_2),
                'enrollment_status' => $employee->enrollment_status,
            ],
            'logs'    => $logs,
            'summary' => $summary,
            'filters' => $request->only(['from', 'to', 'status']),
        ]);
    }

    public function export(Request $request, Employee $employee)
    {
        $query = AttendanceLog::where('id_num', $employee->id_num);

        if ($request->from)   $query->whereDate('date_today', '>=', Carbon::parse($request->from));
        if ($request->to)     $query->whereDate('date_today', '<=', Carbon::parse($request->to));
        if ($request->status) $query->where('status', $request->status);

        $logs = $query->orderBy('date_today', 'desc')->get();

        $rows   = [];
        $rows[] = ['Date','Status','Time In','Time Out','Break 1 Out','Break 1 In','Break 2 Out','Break 2 In','Break 3 Out','Break 3 In','Hours Worked'];

        foreach ($logs as $log) {
            $rows[] = [
                $log->date_today->format('Y-m-d'),
                $log->status,
                $log->time_in?->setTimezone(config('app.timezone'))->format('g:i:s A') ?? '—',
                $log->time_out?->setTimezone(config('app.timezone'))->format('g:i:s A') ?? '—',
                $log->break1_out?->setTimezone(config('app.timezone'))->format('g:i:s A') ?? '—',
                $log->break1_in?->setTimezone(config('app.timezone'))->format('g:i:s A')  ?? '—',
                $log->break2_out?->setTimezone(config('app.timezone'))->format('g:i:s A') ?? '—',
                $log->break2_in?->setTimezone(config('app.timezone'))->format('g:i:s A')  ?? '—',
                $log->break3_out?->setTimezone(config('app.timezone'))->format('g:i:s A') ?? '—',
                $log->break3_in?->setTimezone(config('app.timezone'))->format('g:i:s A')  ?? '—',
                $this->computeWorkedHours($log) ?? '—',
            ];
        }

        $filename = 'logs_' . str_replace(' ', '_', $employee->full_name) . '_' . now()->format('Ymd') . '.csv';

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) fputcsv($handle, $row);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function formatLog($log): array
    {
        return [
            'id'         => $log->id,
            'date_today' => $log->date_today->format('Y-m-d'),
            'date_label' => $log->date_today->format('D, M j Y'),
            'status'     => $log->status,
            'time_in'    => $log->time_in?->setTimezone(config('app.timezone'))->format('g:i:s A'),
            'time_out'   => $log->time_out?->setTimezone(config('app.timezone'))->format('g:i:s A'),
            'break1_out' => $log->break1_out?->setTimezone(config('app.timezone'))->format('g:i:s A'),
            'break1_in'  => $log->break1_in?->setTimezone(config('app.timezone'))->format('g:i:s A'),
            'break2_out' => $log->break2_out?->setTimezone(config('app.timezone'))->format('g:i:s A'),
            'break2_in'  => $log->break2_in?->setTimezone(config('app.timezone'))->format('g:i:s A'),
            'break3_out' => $log->break3_out?->setTimezone(config('app.timezone'))->format('g:i:s A'),
            'break3_in'  => $log->break3_in?->setTimezone(config('app.timezone'))->format('g:i:s A'),
            'worked_hours' => $this->computeWorkedHours($log),
        ];
    }

    private function computeWorkedHours($log): ?string
    {
        if (!$log->time_in || !$log->time_out) return null;
        $total = $log->time_in->diffInMinutes($log->time_out);
        foreach ([['break1_out','break1_in'],['break2_out','break2_in'],['break3_out','break3_in']] as [$out,$in]) {
            if ($log->$out && $log->$in) $total -= $log->$out->diffInMinutes($log->$in);
        }
        $h = intdiv($total, 60); $m = $total % 60;
        return $h > 0 ? $h.'h '.($m > 0 ? $m.'m' : '') : $m.'m';
    }
}