<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ListQuery;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectionController extends Controller
{
    use ListQuery;
    public function index(Request $request)
    {
        $sections = $this->listApply(Section::with(['department', 'head']), $request, [
            'searchable' => ['name', 'department.name'],
            'sortable' => ['name' => 'name', 'created_at' => 'created_at'],
            'defaultSort' => ['id' => 'desc'],
        ])->paginate(10)->withQueryString();
        $departments = Department::where('status', 'active')->get();
        // Optimize: select only necessary fields for dropdown
        $employees = Employee::select('id', 'first_name', 'last_name', 'employee_number')->get();

        return Inertia::render('Admin/Sections/Index', [
            'sections' => $sections,
            'departments' => $departments,
            'employees' => $employees,
            'filters' => $this->listFilters($request),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'head_id' => 'nullable|exists:employees,id',
            'description' => 'nullable|string',
        ]);

        Section::create($request->all());

        return back()->with('success', 'Section created successfully.');
    }

    public function update(Request $request, Section $section)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'head_id' => 'nullable|exists:employees,id',
            'description' => 'nullable|string',
        ]);

        $section->update($request->all());

        return back()->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        $section->delete();

        return back()->with('success', 'Section deleted successfully.');
    }
}
