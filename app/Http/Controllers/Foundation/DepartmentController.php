<?php

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Models\Foundation\Department;
use App\Models\Foundation\Module;
use App\Models\Foundation\User;
use App\Services\Access\ModuleAccess;
use App\Support\Foundation\FoundationWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    private function module(): Module
    {
        return Module::query()->where('MOCode', 'settings.departments')->firstOrFail();
    }

    public function index(Request $request)
    {
        $module = $this->module();
        Gate::authorize('view', $module);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', 'in:active,inactive'], 'sort' => ['nullable', 'in:code,name'], 'direction' => ['nullable', 'in:asc,desc']]);
        $records = Department::query()->where('DP_CONo', $request->user()->US_CONo)
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($q) => $q->where('DPCode', 'like', '%'.$value.'%')->orWhere('DPName', 'like', '%'.$value.'%')))
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('DPActive', $value === 'active'))
            ->orderBy(($filters['sort'] ?? 'name') === 'code' ? 'DPCode' : 'DPName', $filters['direction'] ?? 'asc')->orderBy('DPNo')->paginate(15)->withQueryString();

        return view('foundation.departments', ['title' => 'Departments', 'description' => 'Company reference data, independent of team membership.', 'currentModule' => $module, 'records' => $records]);
    }

    public function show(Request $request, Department $department)
    {
        $module = $this->module();
        Gate::authorize('view', $module);
        abort_unless($department->DP_CONo === $request->user()->US_CONo, 404);

        return view('foundation.department', ['title' => $department->DPName, 'description' => 'Department details.', 'currentModule' => $module, 'department' => $department]);
    }

    public function store(Request $request, FoundationWriter $writer)
    {
        return $this->save($request, $writer, null);
    }

    public function update(Request $request, Department $department, FoundationWriter $writer)
    {
        return $this->save($request, $writer, $department);
    }

    private function save(Request $request, FoundationWriter $writer, ?Department $department)
    {
        Gate::authorize('edit', $module = $this->module());
        $record = $writer->transaction($request->user(), function ($changes) use ($request, $department, $module) {
            $actor = User::query()->whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($actor->USAuthVersion === $request->user()->USAuthVersion && app(ModuleAccess::class)->can($actor, $module, 'edit'), 403);
            if ($department) {
                $department = Department::query()->whereKey($department->getKey())->lockForUpdate()->firstOrFail();
                abort_unless($department->DP_CONo === $actor->US_CONo, 404);
            }
            $data = Validator::make($request->all(), [
                'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('MSDepartment', 'DPCode')->where('DP_CONo', $actor->US_CONo)->ignore($department?->getKey(), 'DPNo')],
                'name' => ['required', 'string', 'max:150'], 'active' => ['required', 'boolean'],
            ])->validate();
            $values = ['DPCode' => $data['code'], 'DPName' => $data['name'], 'DPActive' => $data['active']];

            return $department ? $changes->update($department, $values) : $changes->create(Department::class, $values + ['DP_CONo' => $actor->US_CONo]);
        });

        return redirect()->route('departments.show', $record)->with('status', 'Department saved.');
    }
}
