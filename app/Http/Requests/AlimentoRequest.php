<?php

namespace App\Http\Requests;

use App\Models\Alimento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlimentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'proveedor_ruc' => $this->filled('proveedor_ruc') ? preg_replace('/\D/', '', $this->proveedor_ruc) : null,
            'factura_serie' => $this->filled('factura_serie') ? mb_strtoupper(trim($this->factura_serie)) : null,
            'otros'         => $this->input('otros') ?: 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'penal'                  => ['nullable', Rule::in(array_keys(Alimento::PENALES))],
            'periodo_inicio'         => ['required', 'date'],
            'periodo_fin'            => ['required', 'date', 'after_or_equal:periodo_inicio'],
            'orden_servicio'         => ['nullable', 'string', 'max:50'],
            'fecha_orden_servicio'   => ['nullable', 'date'],
            'registro_siaf'          => ['nullable', 'string', 'max:20'],
            'numero_contrato'        => ['nullable', 'string', 'max:60'],
            'garantia_descripcion'   => ['nullable', 'string', 'max:200'],
            'garantia_monto'         => ['nullable', 'numeric', 'min:0'],
            'garantia_vencimiento'   => ['nullable', 'date'],
            'factura_serie'          => ['nullable', 'string', 'max:10'],
            'factura_numero'         => ['nullable', 'string', 'max:20'],
            'fecha_emision'          => ['required', 'date', 'before_or_equal:today'],
            'proveedor_ruc'          => ['nullable', 'digits:11'],
            'proveedor_nombre'       => ['nullable', 'string', 'max:200'],
            'proveedor_razon_social' => ['required', 'string', 'max:250'],
            'base_imponible'         => ['required', 'numeric', 'min:0'],
            'igv'                    => ['required', 'numeric', 'min:0'],
            'otros'                  => ['nullable', 'numeric', 'min:0'],
            'total'                  => ['required', 'numeric', 'min:0.01'],
            'estado'                 => ['nullable', Rule::in(['pendiente', 'en_proceso', 'observado'])],
            'area_responsable'       => ['nullable', 'string', 'max:100'],
            'descripcion'            => ['nullable', 'string', 'max:500'],
            'observaciones'          => ['nullable', 'string', 'max:500'],
            'archivo_adjunto'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'periodo_inicio'         => 'inicio del periodo',
            'periodo_fin'            => 'fin del periodo',
            'proveedor_ruc'          => 'RUC del proveedor',
            'proveedor_razon_social' => 'razón social del proveedor',
            'fecha_emision'          => 'fecha de emisión',
            'base_imponible'         => 'base imponible',
            'igv'                    => 'IGV',
        ];
    }
}
