<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\AttendanceLog;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today();

        $totalEmployees  = Employee::count();
        $enrolled        = Employee::whereNotNull('fingerprint_1')->whereNotNull('fingerprint_2')->count();
        $presentToday    = AttendanceLog::whereDate('date_today', $today)->whereNotNull('time_in')->count();
        $lateToday       = AttendanceLog::whereDate('date_today', $today)->where('status', 'late')->count();
        $absentToday     = $totalEmployees - AttendanceLog::whereDate('date_today', $today)->count();
        $currentlyOnBreak = AttendanceLog::whereDate('date_today', $today)
            ->where(function($q) {
                $q->whereNotNull('break1_out')->whereNull('break1_in')
                  ->orWhere(fn($q) => $q->whereNotNull('break2_out')->whereNull('break2_in'))
                  ->orWhere(fn($q) => $q->whereNotNull('break3_out')->whereNull('break3_in'));
            })->count();

        $recentActivity = AttendanceLog::with('employee')
            ->whereDate('date_today', $today)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($log) => [
                'id_num'      => $log->id_num,
                'full_name'   => $log->employee?->full_name,
                'store_dept'  => $log->employee?->store_dept,
                'time_in'     => $log->time_in?->setTimezone(config('app.timezone'))->format('g:i A'),
                'time_out'    => $log->time_out?->setTimezone(config('app.timezone'))->format('g:i A'),
                'status'      => $log->status,
                'next_action' => $log->next_action,
            ]);

        $trend = collect(range(6, 0))->map(function($daysAgo) {
            $date = today()->subDays($daysAgo);
            return [
                'date'    => $date->format('M d'),
                'present' => AttendanceLog::whereDate('date_today', $date)->where('status', 'present')->count(),
                'late'    => AttendanceLog::whereDate('date_today', $date)->where('status', 'late')->count(),
            ];
        });

        $unenrolled = Employee::where(function($q) {
            $q->whereNull('fingerprint_1')->orWhereNull('fingerprint_2');
        })->limit(5)->get()
        ->map(fn($e) => [
            'id'            => $e->id,
            'id_num'        => $e->id_num,
            'full_name'     => $e->full_name,
            'store_dept'    => $e->store_dept,
            'has_fp1'       => !empty($e->fingerprint_1),
            'has_fp2'       => !empty($e->fingerprint_2),
            'fingerprint_1' => $e->fingerprint_1,
            'fingerprint_2' => $e->fingerprint_2,
        ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_employees'  => $totalEmployees,
                'enrolled'         => $enrolled,
                'present_today'    => $presentToday,
                'late_today'       => $lateToday,
                'absent_today'     => $absentToday,
                'on_break'         => $currentlyOnBreak,
                'unenrolled_count' => $totalEmployees - $enrolled,
            ],
            'recent_activity' => $recentActivity,
            'trend'           => $trend,
            'unenrolled'      => $unenrolled,
            'today'           => $today->format('l, F j Y'),
        ]);
    }
}