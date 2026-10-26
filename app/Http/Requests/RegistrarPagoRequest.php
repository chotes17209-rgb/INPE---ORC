<?php

namespace App\Http\Requests;

use App\Support\Catalogo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metodo_pago'    => ['required', Rule::in(array_keys(Catalogo::METODOS_PAGO))],
            'numero_voucher' => ['nullable', 'string', 'max:60'],
            'fecha_pago'     => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'metodo_pago'    => 'método de pago',
            'numero_voucher' => 'número de voucher',
            'fecha_pago'     => 'fecha de pago',
        ];
    }
}
