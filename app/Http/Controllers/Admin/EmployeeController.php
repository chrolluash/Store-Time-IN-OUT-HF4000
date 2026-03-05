<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    const POSITIONS = [
        'Store Head',
        'Store Manager',
        'Cashier',
        'Sales Personnel',
    ];

    const DEPARTMENTS = [
        'SM MOA',
        'SM Megamall',
        'SM North EDSA',
        'Glorietta',
        'Robinsons Manila',
        'SM Grand Central',
        'SM Fairview',
        'SM Southmall',
        'ATC',
        'Festival Mall',
        'Ali Mall',
    ];

    public function index(Request $request): Response
    {
        $query = Employee::withCount('logs')->with('todayLog');

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('id_num', 'like', "%{$request->search}%")
                  ->orWhere('store_dept', 'like', "%{$request->search}%")
                  ->orWhere('position', 'like', "%{$request->search}%");
            });
        }

        if ($request->dept) {
            $query->where('store_dept', $request->dept);
        }

        if ($request->position) {
            $query->where('position', $request->position);
        }

        $employees = $query->orderBy('full_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn($e) => [
                'id'                => $e->id,
                'id_num'            => $e->id_num,
                'full_name'         => $e->full_name,
                'position'          => $e->position,
                'store_dept'        => $e->store_dept,
                'shift_from'        => $e->shift_from ? Carbon::createFromTimeString($e->shift_from)->format('g:i A') : null,
                'shift_to'          => $e->shift_to ? Carbon::createFromTimeString($e->shift_to)->format('g:i A') : null,
                'enrollment_status' => $e->enrollment_status,
                'has_fp1'           => !empty($e->fingerprint_1),
                'has_fp2'           => !empty($e->fingerprint_2),
                'fingerprint_1'     => $e->fingerprint_1,
                'fingerprint_2'     => $e->fingerprint_2,
                'logs_count'        => $e->logs_count,
                'today_status'      => $e->todayLog?->status ?? 'no_log',
                'created_at'        => $e->created_at->format('Y-m-d'),
                'updated_at'        => $e->updated_at->format('Y-m-d'),
            ]);

        return Inertia::render('Admin/Employees/Index', [
            'employees'   => $employees,
            'departments' => self::DEPARTMENTS,
            'positions'   => self::POSITIONS,
            'filters'     => $request->only(['search', 'dept', 'position']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Employees/Form', [
            'employee'    => null,
            'mode'        => 'create',
            'positions'   => self::POSITIONS,
            'departments' => self::DEPARTMENTS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_num'     => 'required|string|max:50|unique:employees,id_num',
            'full_name'  => 'required|string|max:255',
            'position'   => 'required|string|in:' . implode(',', self::POSITIONS),
            'store_dept' => 'required|string|in:' . implode(',', self::DEPARTMENTS),
            'shift_from' => 'required|date_format:H:i',
            'shift_to'   => 'required|date_format:H:i',
        ]);

        $employee = Employee::create($validated);

        return redirect()->route('admin.employees.index')
            ->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee): Response
    {
        return Inertia::render('Admin/Employees/Form', [
            'employee' => [
                'id'         => $employee->id,
                'id_num'     => $employee->id_num,
                'full_name'  => $employee->full_name,
                'position'   => $employee->position,
                'store_dept' => $employee->store_dept,
                'shift_from' => $employee->shift_from ? Carbon::createFromTimeString($employee->shift_from)->format('g:i A') : null,
                'shift_to'   => $employee->shift_to ? Carbon::createFromTimeString($employee->shift_to)->format('g:i A') : null,
                'enrollment_status' => $employee->enrollment_status,
            ],
            'mode'        => 'edit',
            'positions'   => self::POSITIONS,
            'departments' => self::DEPARTMENTS,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'full_name'  => 'required|string|max:255',
            'position'   => 'required|string|in:' . implode(',', self::POSITIONS),
            'store_dept' => 'required|string|in:' . implode(',', self::DEPARTMENTS),
            'shift_from' => 'required|date_format:H:i',
            'shift_to'   => 'required|date_format:H:i',
        ]);

        $employee->update($validated);

        return redirect()->route('admin.employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('admin.employees.index')
            ->with('success', 'Employee deleted.');
    }

    public function enroll(Employee $employee): Response
    {
        return Inertia::render('Admin/Employees/Enroll', [
            'employee' => [
                'id'                => $employee->id,
                'id_num'            => $employee->id_num,
                'full_name'         => $employee->full_name,
                'store_dept'        => $employee->store_dept,
                'enrollment_status' => $employee->enrollment_status,
                'has_fp1'           => !empty($employee->fingerprint_1),
                'has_fp2'           => !empty($employee->fingerprint_2),
            ],
        ]);
    }

    public function saveFingerprint(Request $request, Employee $employee)
    {
        $request->validate([
            'finger'   => 'required|in:1,2',
            'template' => 'required|string',
        ]);

        $field = 'fingerprint_' . $request->finger;
        $employee->update([$field => $request->template]);

        return response()->json([
            'success'           => true,
            'message'           => 'Fingerprint saved.',
            'enrollment_status' => $employee->fresh()->enrollment_status,
        ])->withCookie(cookie()->forever('XSRF-TOKEN', csrf_token()));
    }

    public function clearFingerprint(Request $request, Employee $employee)
    {
        $request->validate(['finger' => 'required|in:1,2']);
        $employee->update(['fingerprint_' . $request->finger => null]);
        return response()->json(['success' => true]);
    }

    public function testFingerprint(Request $request, Employee $employee)
    {
        $request->validate(['template' => 'required|string']);

        $incoming = trim($request->template);
        $results  = [];

        foreach ([1, 2] as $finger) {
            $stored = $employee->{'fingerprint_' . $finger};
            if (!$stored) continue;

            if (trim($stored) === $incoming) {
                return response()->json([
                    'matched' => true,
                    'finger'  => $finger,
                    'score'   => 100,
                ]);
            }

            $results[] = $finger;
        }

        return response()->json([
            'matched' => false,
            'checked' => $results,
            'score'   => 0,
        ]);
    }

    // Returns stored template for client-side HF4000 setdata+match test
    public function getFingerprint(Request $request, Employee $employee)
    {
        $request->validate(['finger' => 'required|in:1,2']);
        $template = $employee->{'fingerprint_' . $request->finger};
        return response()->json(['template' => $template]);
    }
}