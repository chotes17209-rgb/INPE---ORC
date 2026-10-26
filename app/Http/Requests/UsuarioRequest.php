<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User|null $usuario */
        $usuario = $this->route('usuario');
        $esNuevo = $usuario === null;

        // Solo un superadministrador puede otorgar el rol super-admin.
        $reglaRol = ['exists:roles,name'];
        if (! $this->user()?->hasRole('super-admin')) {
            $reglaRol[] = Rule::notIn(['super-admin']);
        }

        return [
            'name'      => ['required', 'string', 'max:80'],
            'apellidos' => ['required', 'string', 'max:80'],
            'email'     => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($usuario)],
            'dni'       => ['nullable', 'digits:8', Rule::unique('users', 'dni')->ignore($usuario)],
            'telefono'  => ['nullable', 'string', 'max:15'],
            'cargo'     => ['nullable', 'string', 'max:100'],
            'area'      => ['nullable', 'string', 'max:100'],
            'estado'    => ['required', Rule::in(array_keys(User::ESTADOS))],
            'password'  => [$esNuevo ? 'required' : 'nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'roles'     => ['required', 'array', 'min:1'],
            'roles.*'   => $reglaRol,
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'  => 'Este correo electrónico ya se encuentra registrado.',
            'dni.unique'    => 'Este DNI ya se encuentra registrado.',
            'dni.digits'    => 'El DNI debe tener exactamente 8 dígitos.',
            'roles.required'=> 'Debe asignar al menos un rol.',
            'roles.*.not_in'=> 'No tiene autorización para asignar el rol seleccionado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'      => 'nombres',
            'apellidos' => 'apellidos',
            'password'  => 'contraseña',
        ];
    }
}
