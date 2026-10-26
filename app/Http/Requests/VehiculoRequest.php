<?php

namespace App\Http\Requests;

use App\Models\Vehiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('placa')) {
            $this->merge(['placa' => mb_strtoupper(trim($this->placa))]);
        }
    }

    public function rules(): array
    {
        /** @var Vehiculo|null $vehiculo */
        $vehiculo = $this->route('vehiculo');

        return [
            'placa'                  => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9\-]{6,10}$/', Rule::unique('vehiculos', 'placa')->ignore($vehiculo)],
            'marca'                  => ['required', 'string', 'max:60'],
            'modelo'                 => ['required', 'string', 'max:60'],
            'anio_fabricacion'       => ['required', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'color'                  => ['nullable', 'string', 'max:40'],
            'numero_serie'           => ['nullable', 'string', 'max:60'],
            'numero_motor'           => ['nullable', 'string', 'max:60'],
            'tipo'                   => ['required', Rule::in(array_keys(Vehiculo::TIPOS))],
            'combustible'            => ['required', Rule::in(array_keys(Vehiculo::COMBUSTIBLES))],
            'valor_adquisicion'      => ['required', 'numeric', 'min:0'],
            'fecha_adquisicion'      => ['nullable', 'date', 'before_or_equal:today'],
            // El kilometraje no puede retroceder respecto al último registrado.
            'kilometraje_actual'     => ['required', 'integer', 'min:' . ($vehiculo?->kilometraje_actual ?? 0)],
            'kilometraje_siguiente_mantenimiento' => ['nullable', 'integer', 'gte:kilometraje_actual'],
            'fecha_soat'             => ['nullable', 'date'],
            'fecha_revision_tecnica' => ['nullable', 'date'],
            'fecha_seguro'           => ['nullable', 'date'],
            'estado'                 => ['required', Rule::in(array_keys(Vehiculo::ESTADOS))],
            'area_asignada'          => ['nullable', 'string', 'max:100'],
            'responsable_id'         => ['nullable', 'exists:users,id'],
            'observaciones'          => ['nullable', 'string', 'max:500'],
            'imagen'                 => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'placa.unique'           => 'Esta placa ya se encuentra registrada.',
            'placa.regex'            => 'La placa debe tener un formato válido (por ejemplo: ABC-123).',
            'kilometraje_actual.min' => 'El kilometraje no puede ser menor al último registrado (:min km).',
        ];
    }

    public function attributes(): array
    {
        return [
            'anio_fabricacion'       => 'año de fabricación',
            'valor_adquisicion'      => 'valor de adquisición',
            'kilometraje_actual'     => 'kilometraje actual',
            'kilometraje_siguiente_mantenimiento' => 'kilometraje del próximo mantenimiento',
            'fecha_soat'             => 'vencimiento del SOAT',
            'fecha_revision_tecnica' => 'vencimiento de la revisión técnica',
            'responsable_id'         => 'responsable',
        ];
    }
}
