<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** Roles en los que se basa la matriz de permisos; no pueden renombrarse ni eliminarse. */
    private const ROLES_SISTEMA = ['super-admin', 'admin', 'contador', 'asistente', 'visualizador'];

    public function index()
    {
        $roles = Role::withCount('users')->orderBy('id')->get();

        return view('admin.roles.index', ['roles' => $roles, 'rolesSistema' => self::ROLES_SISTEMA]);
    }

    public function create()
    {
        return view('admin.roles.create');
    }

    public function store(Request $request)
    {
        $request->validate(
            ['name' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9\-]+$/', 'unique:roles,name']],
            ['name.regex' => 'Use solo letras minúsculas, números y guiones (por ejemplo: jefe-area).'],
            ['name' => 'nombre del rol']
        );

        Role::create(['name' => $request->name, 'guard_name' => 'web']);

        return redirect()->route('admin.roles.index')->with('success', 'El rol fue creado correctamente.');
    }

    public function edit(Role $role)
    {
        if (in_array($role->name, self::ROLES_SISTEMA, true)) {
            return redirect()->route('admin.roles.index')->with('info', 'Los roles del sistema no pueden modificarse.');
        }

        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        abort_if(in_array($role->name, self::ROLES_SISTEMA, true), 403, 'Los roles del sistema no pueden modificarse.');

        $request->validate(
            ['name' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9\-]+$/', Rule::unique('roles', 'name')->ignore($role)]],
            ['name.regex' => 'Use solo letras minúsculas, números y guiones (por ejemplo: jefe-area).'],
            ['name' => 'nombre del rol']
        );

        $role->update(['name' => $request->name]);

        return redirect()->route('admin.roles.index')->with('success', 'El rol fue actualizado.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, self::ROLES_SISTEMA, true)) {
            return back()->with('error', 'Los roles del sistema no pueden eliminarse.');
        }
        if ($role->users()->exists()) {
            return back()->with('error', 'No se puede eliminar un rol que tiene usuarios asignados.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'El rol fue eliminado.');
    }
}
