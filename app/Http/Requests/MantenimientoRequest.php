<?php

namespace App\Http\Requests;

use App\Models\VehiculoMantenimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('factura_serie')) {
            $this->merge(['factura_serie' => mb_strtoupper(trim($this->factura_serie))]);
        }
    }

    public function rules(): array
    {
        $esNuevo = $this->isMethod('post');

        return [
            'vehiculo_id'               => [$esNuevo ? 'required' : 'sometimes', 'exists:vehiculos,id'],
            'tipo_mantenimiento'        => ['required', Rule::in(array_keys(VehiculoMantenimiento::TIPOS))],
            'descripcion'               => ['required', 'string', 'max:500'],
            'fecha_programada'          => ['required', 'date'],
            'costo_estimado'            => ['required', 'numeric', 'min:0'],
            'costo_real'                => ['nullable', 'numeric', 'min:0'],
            'total'                     => ['nullable', 'numeric', 'min:0'],
            'taller'                    => ['nullable', 'string', 'max:100'],
            'tecnico_responsable'       => ['nullable', 'string', 'max:100'],
            'kilometraje_mantenimiento' => ['nullable', 'integer', 'min:0'],
            'orden_servicio'            => ['nullable', 'string', 'max:50'],
            'fecha_orden_servicio'      => ['nullable', 'date'],
            'registro_siaf'             => ['nullable', 'integer', 'min:0'],
            'factura_serie'             => ['nullable', 'string', 'max:10'],
            'factura_numero_completo'   => ['nullable', 'string', 'max:20'],
            'factura_fecha'             => ['nullable', 'date'],
            'observaciones'             => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'vehiculo_id'               => 'vehículo',
            'tipo_mantenimiento'        => 'tipo de mantenimiento',
            'fecha_programada'          => 'fecha programada',
            'costo_estimado'            => 'costo estimado',
            'costo_real'                => 'costo real',
            'kilometraje_mantenimiento' => 'kilometraje',
            'registro_siaf'             => 'registro SIAF',
            'factura_numero_completo'   => 'número de factura',
        ];
    }
}
