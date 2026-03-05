<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class LogController extends Controller
{
    const POSITIONS = ['Store Head', 'Store Manager', 'Cashier', 'Sales Personnel'];
    const DEPARTMENTS = [
        'SM MOA', 'SM Megamall', 'SM North EDSA', 'Glorietta', 'Robinsons Manila',
        'SM Grand Central', 'SM Fairview', 'SM Southmall', 'ATC', 'Festival Mall', 'Ali Mall',
    ];

    public function index(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : today();

        $query = AttendanceLog::with('employee')
            ->whereDate('date_today', $date);

        if ($request->dept) {
            $query->whereHas('employee', fn($q) => $q->where('store_dept', $request->dept));
        }

        if ($request->position) {
            $query->whereHas('employee', fn($q) => $q->where('position', $request->position));
        }

        if ($request->search) {
            $query->whereHas('employee', function($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('id_num', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $logs = $query->orderBy('time_in')
            ->paginate(20)
            ->withQueryString()
            ->through(fn($log) => [
                'id'               => $log->id,
                'id_num'           => $log->id_num,
                'full_name'        => $log->employee?->full_name,
                'position'         => $log->employee?->position,
                'store_dept'       => $log->employee?->store_dept,
                'shift_from'       => $log->employee?->shift_from
                                        ? Carbon::createFromTimeString($log->employee->shift_from)->format('g:i A')
                                        : null,
                'shift_to'         => $log->employee?->shift_to
                                        ? Carbon::createFromTimeString($log->employee->shift_to)->format('g:i A')
                                        : null,
                'date_today'       => $log->date_today->format('Y-m-d'),
                'time_in'          => $log->time_in?->setTimezone(config('app.timezone'))->format('g:i:s A'),
                'time_out'         => $log->time_out?->setTimezone(config('app.timezone'))->format('g:i:s A'),
                'break1_out'       => $log->break1_out?->setTimezone(config('app.timezone'))->format('g:i:s A'),
                'break1_in'        => $log->break1_in?->setTimezone(config('app.timezone'))->format('g:i:s A'),
                'break2_out'       => $log->break2_out?->setTimezone(config('app.timezone'))->format('g:i:s A'),
                'break2_in'        => $log->break2_in?->setTimezone(config('app.timezone'))->format('g:i:s A'),
                'break3_out'       => $log->break3_out?->setTimezone(config('app.timezone'))->format('g:i:s A'),
                'break3_in'        => $log->break3_in?->setTimezone(config('app.timezone'))->format('g:i:s A'),
                'status'           => $log->status,
                'worked_hours'     => $this->computeWorkedHours($log),
                'overtime_minutes' => $log->overtime_minutes,
                'next_action'      => $log->next_action,
            ]);

        $summary = [
            'present' => AttendanceLog::whereDate('date_today', $date)->where('status', 'present')->count(),
            'late'    => AttendanceLog::whereDate('date_today', $date)->where('status', 'late')->count(),
            'absent'  => Employee::count() - AttendanceLog::whereDate('date_today', $date)->count(),
            'total'   => Employee::count(),
        ];

        return Inertia::render('Admin/Logs/Index', [
            'logs'          => $logs,
            'summary'       => $summary,
            'departments'   => self::DEPARTMENTS,
            'positions'     => self::POSITIONS,
            'filters'       => $request->only(['date', 'search', 'dept', 'position', 'status']),
            'selected_date' => $date->format('Y-m-d'),
        ]);
    }

    private function computeWorkedHours($log): ?string
    {
        if (!$log->time_in || !$log->time_out) return null;

        $total = $log->time_in->diffInMinutes($log->time_out);

        // Subtract completed break durations
        foreach ([['break1_out','break1_in'],['break2_out','break2_in'],['break3_out','break3_in']] as [$out, $in]) {
            if ($log->$out && $log->$in) {
                $total -= $log->$out->diffInMinutes($log->$in);
            }
        }

        $hours   = intdiv($total, 60);
        $minutes = $total % 60;

        return $hours > 0
            ? $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '')
            : $minutes . 'm';
    }
}