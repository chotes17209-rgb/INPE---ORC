<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $usuarios = User::with('roles')
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $b = $request->buscar;
                $query->where(fn ($q) => $q
                    ->whereLike('name', "%{$b}%")
                    ->orWhereLike('apellidos', "%{$b}%")
                    ->orWhereLike('email', "%{$b}%")
                    ->orWhereLike('dni', "%{$b}%"));
            })
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->when($request->filled('rol'), fn ($q) => $q->role($request->rol))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', ['usuarios' => $usuarios, 'roles' => $this->rolesAsignables()]);
    }

    public function create()
    {
        return view('usuarios.create', [
            'usuario' => new User(['estado' => 'activo', 'area' => 'Administración']),
            'roles'   => $this->rolesAsignables(),
        ]);
    }

    public function store(UsuarioRequest $request)
    {
        $data = collect($request->validated())->except('roles')->all();

        $usuario = User::create($data);
        $usuario->syncRoles($request->roles);

        return $this->exito("Se creó la cuenta de {$usuario->nombre_completo}.", route('admin.usuarios.show', $usuario));
    }

    public function show(User $usuario)
    {
        $usuario->load('roles');

        $actividad = AuditLog::where('user_id', $usuario->id)->latest()->orderByDesc('id')->take(15)->get();

        return view('usuarios.show', compact('usuario', 'actividad'));
    }

    public function edit(User $usuario)
    {
        $this->verificarJerarquia($usuario);
        $usuario->load('roles');

        return view('usuarios.edit', ['usuario' => $usuario, 'roles' => $this->rolesAsignables()]);
    }

    public function update(UsuarioRequest $request, User $usuario)
    {
        $this->verificarJerarquia($usuario);

        $data = collect($request->validated())->except('roles')->all();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Evita que un administrador se quite a sí mismo el acceso.
        if ($usuario->is(auth()->user())) {
            $data['estado'] = 'activo';
        }

        $usuario->update($data);
        if (! $usuario->is(auth()->user())) {
            $usuario->syncRoles($request->roles);
        }

        return $this->exito('Los datos del usuario fueron actualizados.', route('admin.usuarios.show', $usuario));
    }

    public function destroy(User $usuario)
    {
        if ($usuario->is(auth()->user())) {
            return $this->aviso('No puede eliminar su propia cuenta.');
        }
        $this->verificarJerarquia($usuario);

        $usuario->delete();

        return $this->exito("Se eliminó la cuenta de {$usuario->nombre_completo}.", route('admin.usuarios.index'), true);
    }

    public function toggleEstado(User $usuario)
    {
        if ($usuario->is(auth()->user())) {
            return $this->aviso('No puede cambiar el estado de su propia cuenta.');
        }
        $this->verificarJerarquia($usuario);

        $usuario->update(['estado' => $usuario->estado === 'activo' ? 'inactivo' : 'activo']);

        return $this->exito("La cuenta de {$usuario->nombre_completo} quedó " . mb_strtolower($usuario->estado_label) . '.', url()->previous());
    }

    public function permisos(User $usuario)
    {
        $this->verificarJerarquia($usuario);
        $usuario->load('roles');

        return view('usuarios.permisos', ['usuario' => $usuario, 'roles' => $this->rolesAsignables()]);
    }

    public function actualizarPermisos(Request $request, User $usuario)
    {
        $this->verificarJerarquia($usuario);

        if ($usuario->is(auth()->user())) {
            return $this->aviso('No puede modificar sus propios roles.');
        }

        $request->validate(
            ['roles' => ['required', 'array', 'min:1'], 'roles.*' => ['in:' . $this->rolesAsignables()->pluck('name')->implode(',')]],
            ['roles.required' => 'Debe asignar al menos un rol.', 'roles.*.in' => 'No tiene autorización para asignar el rol seleccionado.']
        );

        $usuario->syncRoles($request->roles);

        return $this->exito('Los roles del usuario fueron actualizados.', route('admin.usuarios.show', $usuario));
    }

    /** Solo un superadministrador puede gestionar cuentas de superadministrador. */
    private function verificarJerarquia(User $usuario): void
    {
        abort_if(
            $usuario->hasRole('super-admin') && ! auth()->user()->hasRole('super-admin'),
            403,
            'Solo un superadministrador puede modificar esta cuenta.'
        );
    }

    private function rolesAsignables()
    {
        return Role::orderBy('id')->get()
            ->when(! auth()->user()->hasRole('super-admin'), fn ($roles) => $roles->where('name', '!=', 'super-admin'))
            ->values();
    }
}
