/* ==========================================================================
   Sistema Integrado de Gestión Contable — INPE Oficina Regional Centro
   Comportamientos comunes de la interfaz
   ========================================================================== */
(function () {
    'use strict';

    const $  = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
    const csrf = () => $('meta[name="csrf-token"]')?.content || '';

    const almacen = {
        get(k) { try { return window.localStorage.getItem(k); } catch (e) { return null; } },
        set(k, v) { try { window.localStorage.setItem(k, v); } catch (e) { /* sin almacenamiento */ } },
    };

    /** Formato de moneda peruana: S/ 1,234.50 */
    const soles = (n) => 'S/ ' + (Number(n) || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    window.ERP = { soles, csrf };

    /* ── Barra lateral ─────────────────────────────────────────────────── */
    function iniciarBarraLateral() {
        const body = document.body;
        const esMovil = () => window.matchMedia('(max-width: 991.98px)').matches;

        if (!esMovil() && almacen.get('erp.sidebar') === 'collapsed') {
            body.classList.add('sidebar-collapsed');
        }

        $$('[data-toggle-sidebar]').forEach(btn => btn.addEventListener('click', () => {
            if (esMovil()) {
                body.classList.toggle('sidebar-open');
            } else {
                const colapsado = body.classList.toggle('sidebar-collapsed');
                almacen.set('erp.sidebar', colapsado ? 'collapsed' : 'expanded');
            }
        }));

        $('.sidebar-backdrop')?.addEventListener('click', () => body.classList.remove('sidebar-open'));
    }

    /* ── Mensajes emergentes ───────────────────────────────────────────── */
    function iniciarToasts() {
        $$('.toast').forEach(el => bootstrap.Toast.getOrCreateInstance(el, { delay: 6000 }).show());
    }

    window.ERP.notificar = function (mensaje, tipo = 'success') {
        const cont = $('#toastContainer');
        if (!cont) return;
        const iconos = { success: 'check-circle-fill', danger: 'x-octagon-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
        const el = document.createElement('div');
        el.className = `toast align-items-center text-bg-${tipo} border-0`;
        el.setAttribute('role', 'status');
        el.innerHTML = `<div class="d-flex"><div class="toast-body d-flex gap-2 align-items-center"><i class="bi bi-${iconos[tipo] || iconos.info}"></i><span></span></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button></div>`;
        el.querySelector('span').textContent = mensaje;
        cont.appendChild(el);
        bootstrap.Toast.getOrCreateInstance(el, { delay: 6000 }).show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    };

    /* ── Confirmación de acciones ──────────────────────────────────────── */
    // Uso: <form data-confirm="¿Desea eliminar…?" data-confirm-title="Eliminar registro" data-confirm-btn="Eliminar" data-confirm-variant="danger">
    function iniciarConfirmaciones() {
        const modalEl = $('#modalConfirmar');
        if (!modalEl) return;
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        let formPendiente = null;

        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!form.matches('form[data-confirm]') || form.dataset.confirmado === '1') return;
            e.preventDefault();
            formPendiente = form;

            const variante = form.dataset.confirmVariant || 'danger';
            $('#modalConfirmarTitulo').textContent = form.dataset.confirmTitle || 'Confirmar acción';
            $('#modalConfirmarMensaje').textContent = form.dataset.confirm;
            const btn = $('#modalConfirmarAceptar');
            btn.textContent = form.dataset.confirmBtn || 'Confirmar';
            btn.className = `btn btn-${variante}`;
            const icono = $('#modalConfirmarIcono');
            icono.className = `modal-icon bg-${variante}-subtle text-${variante}`;
            modal.show();
        }, true);

        $('#modalConfirmarAceptar').addEventListener('click', () => {
            if (!formPendiente) return;
            formPendiente.dataset.confirmado = '1';
            modal.hide();
            if (typeof formPendiente.requestSubmit === 'function') formPendiente.requestSubmit();
            else formPendiente.submit();
        });
        modalEl.addEventListener('hidden.bs.modal', () => {
            if (formPendiente && formPendiente.dataset.confirmado !== '1') formPendiente = null;
        });
    }

    /* ── Evitar envíos duplicados ──────────────────────────────────────── */
    function iniciarEnvioUnico() {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (e.defaultPrevented || (form.method || '').toLowerCase() === 'get' || form.hasAttribute('data-no-bloquear')) return;
            if (form.dataset.enviando === '1') { e.preventDefault(); return; }
            form.dataset.enviando = '1';
            $$('button[type="submit"], button:not([type])', form).forEach(btn => {
                btn.disabled = true;
                if (btn.dataset.textoEnvio !== undefined || btn.classList.contains('btn-primary') || btn.classList.contains('btn-success')) {
                    btn.dataset.html = btn.innerHTML;
                    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${btn.dataset.textoEnvio || 'Procesando…'}`;
                }
            });
            // Los formularios que descargan archivos no cambian de página: se reactivan.
            setTimeout(() => {
                form.dataset.enviando = '';
                $$('button[disabled]', form).forEach(btn => { btn.disabled = false; if (btn.dataset.html) btn.innerHTML = btn.dataset.html; });
            }, 8000);
        });
    }

    /* ── Modal compartido de registro de pago ──────────────────────────── */
    // Uso: <button data-pago-url="…" data-pago-codigo="ALI-2025-00001" data-pago-detalle="Proveedor" data-pago-total="1500.00">
    function iniciarModalPago() {
        const modalEl = $('#modalPago');
        if (!modalEl) return;
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-pago-url]');
            if (!btn) return;
            const form = $('form', modalEl);
            form.action = btn.dataset.pagoUrl;
            form.reset();
            form.dataset.enviando = '';
            $$('button[disabled]', form).forEach(b => { b.disabled = false; if (b.dataset.html) b.innerHTML = b.dataset.html; });
            $('[data-campo="codigo"]', modalEl).textContent = btn.dataset.pagoCodigo || '';
            $('[data-campo="detalle"]', modalEl).textContent = btn.dataset.pagoDetalle || '';
            $('[data-campo="total"]', modalEl).textContent = soles(btn.dataset.pagoTotal);
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    }

    /* ── Cálculo automático de importes ────────────────────────────────── */
    // Uso: inputs con data-suma="grupo" y un input con data-total="grupo".
    function iniciarTotales() {
        const grupos = new Set($$('[data-suma]').map(el => el.dataset.suma));
        grupos.forEach(grupo => {
            const sumandos = $$(`[data-suma="${grupo}"]`);
            const total = $(`[data-total="${grupo}"]`);
            const vista = $(`[data-total-vista="${grupo}"]`);
            const recalcular = () => {
                const suma = sumandos.reduce((acc, el) => acc + (parseFloat(el.value) || 0), 0);
                if (total) total.value = suma.toFixed(2);
                if (vista) vista.textContent = soles(suma);
            };
            sumandos.forEach(el => el.addEventListener('input', recalcular));
            if (total && vista) total.addEventListener('input', () => { vista.textContent = soles(total.value); });
            if (vista) vista.textContent = soles(total ? total.value : 0);
        });

        // Botón "Calcular IGV (18 %)": data-igv-base="#idBase" data-igv-destino="#idIgv"
        $$('[data-igv-base]').forEach(btn => btn.addEventListener('click', () => {
            const base = parseFloat($(btn.dataset.igvBase)?.value) || 0;
            const destino = $(btn.dataset.igvDestino);
            if (!destino) return;
            destino.value = (Math.round(base * 0.18 * 100) / 100).toFixed(2);
            destino.dispatchEvent(new Event('input', { bubbles: true }));
        }));
    }

    /* ── Consulta de RUC (SUNAT) ───────────────────────────────────────── */
    // Uso: <button data-ruc-url="/servicios/ruc/__RUC__" data-ruc-input="#ruc" data-ruc-razon="#razon" data-ruc-nombre="#nombre" data-ruc-direccion="#dir" data-ruc-estado="#estado">
    function iniciarConsultaRuc() {
        $$('[data-ruc-url]').forEach(btn => {
            const input = $(btn.dataset.rucInput);
            const estado = $(btn.dataset.rucEstado);
            const mostrar = (texto, clase) => { if (estado) { estado.textContent = texto; estado.className = `form-text ${clase}`; } };

            input?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); btn.click(); } });
            input?.addEventListener('input', () => { input.value = input.value.replace(/\D/g, '').slice(0, 11); });

            btn.addEventListener('click', async () => {
                const ruc = (input?.value || '').trim();
                if (!/^\d{11}$/.test(ruc)) { mostrar('Ingrese un RUC de 11 dígitos.', 'text-danger'); input?.focus(); return; }

                btn.disabled = true;
                mostrar('Consultando SUNAT…', 'text-muted');
                try {
                    const r = await fetch(btn.dataset.rucUrl.replace('__RUC__', ruc), { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    if (!d.encontrado) { mostrar(d.mensaje || 'No se encontró el RUC.', 'text-danger'); return; }
                    const asignar = (sel, valor) => { const el = sel && $(sel); if (el && valor) el.value = valor; };
                    asignar(btn.dataset.rucRazon, d.razon_social);
                    asignar(btn.dataset.rucNombre, d.nombre_comercial || d.razon_social);
                    asignar(btn.dataset.rucDireccion, d.direccion);
                    const situacion = [d.estado, d.condicion].filter(Boolean).join(' · ');
                    mostrar(`Contribuyente encontrado${situacion ? ' — ' + situacion : ''}.`, d.estado && d.estado !== 'ACTIVO' ? 'text-warning' : 'text-success');
                } catch (err) {
                    mostrar('No fue posible consultar el RUC. Ingrese los datos manualmente.', 'text-danger');
                } finally {
                    btn.disabled = false;
                }
            });
        });
    }

    /* ── Utilidades de formularios ─────────────────────────────────────── */
    function iniciarFormularios() {
        // Campos en mayúsculas (placas, series de comprobantes)
        $$('input[data-mayusculas]').forEach(el => el.addEventListener('input', () => {
            const pos = el.selectionStart;
            el.value = el.value.toUpperCase();
            try { el.setSelectionRange(pos, pos); } catch (e) { /* tipo no compatible */ }
        }));

        // Solo dígitos (DNI, RUC, SIAF)
        $$('input[data-solo-digitos]').forEach(el => el.addEventListener('input', () => {
            el.value = el.value.replace(/\D/g, '');
        }));

        // Contador de caracteres para áreas de texto con maxlength
        $$('textarea[maxlength][data-contador]').forEach(el => {
            const ayuda = document.createElement('div');
            ayuda.className = 'form-text text-end';
            el.insertAdjacentElement('afterend', ayuda);
            const actualizar = () => { ayuda.textContent = `${el.value.length} / ${el.maxLength} caracteres`; };
            el.addEventListener('input', actualizar);
            actualizar();
        });

        // Selectores de filtro que envían el formulario al cambiar
        $$('select[data-autoenviar]').forEach(el => el.addEventListener('change', () => el.form?.requestSubmit()));

        // Mostrar / ocultar contraseña
        $$('[data-ver-password]').forEach(btn => btn.addEventListener('click', () => {
            const input = $(btn.dataset.verPassword);
            if (!input) return;
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            btn.querySelector('i')?.classList.toggle('bi-eye', visible);
            btn.querySelector('i')?.classList.toggle('bi-eye-slash', !visible);
            btn.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        }));

        // Desplazar al primer campo con error
        const conError = $('.is-invalid');
        if (conError) { conError.scrollIntoView({ behavior: 'smooth', block: 'center' }); conError.focus({ preventScroll: true }); }
    }

    /* ── Tooltips ──────────────────────────────────────────────────────── */
    function iniciarTooltips() {
        $$('[data-bs-toggle="tooltip"], [title][data-tooltip]').forEach(el => bootstrap.Tooltip.getOrCreateInstance(el));
    }

    /* ── Tema de los gráficos ──────────────────────────────────────────── */
    function iniciarGraficos() {
        if (!window.Chart) return;
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#5d6878';
        Chart.defaults.borderColor = '#edf0f4';
        Chart.defaults.plugins.tooltip.backgroundColor = '#0b2440';
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.callbacks.label = (ctx) => {
            const v = ctx.parsed?.y ?? ctx.parsed;
            return `${ctx.dataset.label || ctx.label}: ${soles(v)}`;
        };
    }
    window.ERP.colores = ['#12355b', '#b08a2e', '#1f6391', '#1d7148', '#a6192e', '#5b6b8c', '#7c5c9e', '#3f8f8b', '#c2703d', '#8a94a3'];

    document.addEventListener('DOMContentLoaded', () => {
        iniciarBarraLateral();
        iniciarToasts();
        iniciarConfirmaciones();
        iniciarEnvioUnico();
        iniciarModalPago();
        iniciarTotales();
        iniciarConsultaRuc();
        iniciarFormularios();
        iniciarTooltips();
        iniciarGraficos();
    });
})();
