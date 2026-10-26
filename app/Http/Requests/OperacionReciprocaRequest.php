<?php

namespace App\Http\Requests;

use App\Models\OperacionReciproca;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OperacionReciprocaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ruc_entidad'   => $this->filled('ruc_entidad') ? preg_replace('/\D/', '', $this->ruc_entidad) : null,
            'factura_serie' => $this->filled('factura_serie') ? mb_strtoupper(trim($this->factura_serie)) : null,
            'igv'           => $this->input('igv') ?: 0,
            'otros'         => $this->input('otros') ?: 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'tipo_servicio'            => ['required', Rule::in(array_keys(OperacionReciproca::tipos()))],
            'entidad_proveedora'       => ['required', 'string', 'max:200'],
            'razon_social'             => ['nullable', 'string', 'max:250'],
            'direccion_entidad'        => ['nullable', 'string', 'max:250'],
            'ruc_entidad'              => ['nullable', 'digits:11'],
            'numero_contrato'          => ['nullable', 'string', 'max:60'],
            'servicio_prestado'        => ['nullable', 'string', 'max:200'],
            'vigencia_contrato_inicio' => ['nullable', 'date'],
            'vigencia_contrato_fin'    => ['nullable', 'date', 'after_or_equal:vigencia_contrato_inicio'],
            'usuario_servicio'         => ['nullable', 'string', 'max:150'],
            'penal_pertenece'          => ['nullable', 'string', 'max:50'],
            'factura_serie'            => ['nullable', 'string', 'max:10'],
            'numero_factura'           => ['nullable', 'string', 'max:20'],
            'fecha_emision'            => ['required', 'date', 'before_or_equal:today'],
            'fecha_facturacion'        => ['nullable', 'date'],
            'fecha_vencimiento'        => ['required', 'date', 'after_or_equal:fecha_emision'],
            'base_imponible'           => ['nullable', 'numeric', 'min:0'],
            'monto'                    => ['required', 'numeric', 'min:0'],
            'igv'                      => ['nullable', 'numeric', 'min:0'],
            'otros'                    => ['nullable', 'numeric', 'min:0'],
            'total'                    => ['required', 'numeric', 'min:0.01'],
            'orden_servicio'           => ['nullable', 'string', 'max:50'],
            'fecha_orden_servicio'     => ['nullable', 'date'],
            'registro_siaf'            => ['nullable', 'integer', 'min:0'],
            'periodo_mes'              => ['required', 'integer', 'between:1,12'],
            'periodo_anio'             => ['required', 'integer', 'min:2000', 'max:2099'],
            'area_responsable'         => ['nullable', 'string', 'max:100'],
            'descripcion'              => ['nullable', 'string', 'max:500'],
            'observaciones'            => ['nullable', 'string', 'max:500'],
            'archivo_adjunto'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_servicio'         => 'tipo de servicio',
            'entidad_proveedora'    => 'entidad proveedora',
            'ruc_entidad'           => 'RUC de la entidad',
            'fecha_emision'         => 'fecha de emisión',
            'fecha_vencimiento'     => 'fecha de vencimiento',
            'vigencia_contrato_fin' => 'fin de vigencia del contrato',
            'periodo_mes'           => 'mes del periodo',
            'periodo_anio'          => 'año del periodo',
            'registro_siaf'         => 'registro SIAF',
        ];
    }
}
