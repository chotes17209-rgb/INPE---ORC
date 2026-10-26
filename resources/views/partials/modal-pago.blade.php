{{-- Modal compartido para registrar el pago de un comprobante (alimentos u operaciones recíprocas). --}}
@php $metodos = \App\Support\Catalogo::METODOS_PAGO; @endphp
<div class="modal fade" id="modalPago" tabindex="-1" aria-labelledby="modalPagoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="#">
                @csrf @method('PATCH')
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="modalPagoTitulo"><i class="bi bi-cash-coin me-2 text-success" aria-hidden="true"></i>Registrar pago</h2>
                        <div class="small text-muted"><span class="codigo" data-campo="codigo"></span> · <span data-campo="detalle"></span></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="total-box d-flex justify-content-between align-items-center mb-3">
                        <span class="total-box__label">Importe a pagar</span>
                        <span class="total-box__value" data-campo="total"></span>
                    </div>
                    <div class="mb-3">
                        <x-label for="pago_metodo" requerido>Método de pago</x-label>
                        <select name="metodo_pago" id="pago_metodo" class="form-select" required>
                            <option value="">Seleccione…</option>
                            @foreach($metodos as $valor => $texto)
                                <option value="{{ $valor }}">{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <x-label for="pago_fecha" requerido>Fecha de pago</x-label>
                            <input type="date" name="fecha_pago" id="pago_fecha" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-sm-6">
                            <x-label for="pago_voucher">N° de voucher u operación</x-label>
                            <input type="text" name="numero_voucher" id="pago_voucher" class="form-control" maxlength="60" placeholder="Opcional">
                        </div>
                    </div>
                    <p class="form-text mb-0 mt-3"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Una vez registrado el pago, el comprobante ya no podrá modificarse ni eliminarse.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" data-texto-envio="Registrando…"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Confirmar pago</button>
                </div>
            </form>
        </div>
    </div>
</div>
