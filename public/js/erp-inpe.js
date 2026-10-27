/* ==========================================================================
   Sistema Integrado de Gestión Administrativa — INPE Oficina Regional Centro
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
            $('i', icono).className = `bi bi-${variante === 'danger' ? 'trash3' : 'question-circle'}`;
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
            if (seEnviaEnSegundoPlano(form)) return;
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
            limpiarErrores(form);
            $('[data-campo="codigo"]', modalEl).textContent = btn.dataset.pagoCodigo || '';
            $('[data-campo="detalle"]', modalEl).textContent = btn.dataset.pagoDetalle || '';
            $('[data-campo="total"]', modalEl).textContent = soles(btn.dataset.pagoTotal);
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    }

    /* ── Cálculo automático de importes ────────────────────────────────── */
    // Uso: inputs con data-suma="grupo" y un input con data-total="grupo".
    function iniciarTotales(root = document) {
        const grupos = new Set($$('[data-suma]', root).map(el => el.dataset.suma));
        grupos.forEach(grupo => {
            const sumandos = $$(`[data-suma="${grupo}"]`, root);
            const total = $(`[data-total="${grupo}"]`, root);
            const vista = $(`[data-total-vista="${grupo}"]`, root);
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
        $$('[data-igv-base]', root).forEach(btn => btn.addEventListener('click', () => {
            const base = parseFloat($(btn.dataset.igvBase, root)?.value) || 0;
            const destino = $(btn.dataset.igvDestino, root);
            if (!destino) return;
            destino.value = (Math.round(base * 0.18 * 100) / 100).toFixed(2);
            destino.dispatchEvent(new Event('input', { bubbles: true }));
        }));
    }

    /* ── Consulta de RUC (SUNAT) en ambos sentidos ─────────────────────── */
    // Uso: <button data-ruc-url="/servicios/ruc/__RUC__" data-ruc-buscar="/servicios/ruc-buscar"
    //              data-ruc-input="#ruc" data-ruc-razon="#razon" data-ruc-nombre="#nombre"
    //              data-ruc-direccion="#dir" data-ruc-estado="#estado" [data-ruc-sugerir="#campo"]>
    //  - Al escribir los 11 dígitos del RUC se completa la razón social.
    //  - Al escribir la razón social se sugieren contribuyentes con su RUC.
    function iniciarConsultaRuc(root = document) {
        $$('[data-ruc-url]', root).forEach(btn => {
            const input = $(btn.dataset.rucInput, root);
            const razon = $(btn.dataset.rucSugerir || btn.dataset.rucRazon, root);
            const estado = $(btn.dataset.rucEstado, root);
            const mostrar = (texto, clase) => { if (estado) { estado.textContent = texto; estado.className = `form-text ruc-estado ${clase}`; } };
            const asignar = (sel, valor) => { const el = sel && $(sel, root); if (el && valor) { el.value = valor; el.classList.remove('is-invalid'); } };
            let ultimo = '';

            const completar = (d) => {
                if (input && d.ruc) input.value = d.ruc;
                asignar(btn.dataset.rucRazon, d.razon_social);
                asignar(btn.dataset.rucNombre, d.nombre_comercial || d.razon_social);
                asignar(btn.dataset.rucDireccion, d.direccion);
                const situacion = [d.estado, d.condicion].filter(Boolean).join(' · ');
                mostrar(`Contribuyente encontrado${situacion ? ' — ' + situacion : ''}.`, d.estado && d.estado !== 'ACTIVO' ? 'text-warning' : 'text-success');
            };

            const consultar = async () => {
                const ruc = (input?.value || '').trim();
                if (!/^\d{11}$/.test(ruc)) { mostrar('El RUC tiene 11 dígitos.', 'text-danger'); input?.focus(); return; }
                ultimo = ruc;
                btn.disabled = true;
                mostrar('Buscando en SUNAT…', 'text-muted');
                try {
                    const r = await fetch(btn.dataset.rucUrl.replace('__RUC__', ruc), { headers: { 'Accept': 'application/json' } });
                    const d = await r.json();
                    if (!d.encontrado) { mostrar(d.mensaje || 'No se encontró el RUC. Escriba los datos manualmente.', 'text-danger'); return; }
                    completar(d);
                } catch (err) {
                    mostrar('No fue posible consultar el RUC. Escriba los datos manualmente.', 'text-danger');
                } finally {
                    btn.disabled = false;
                }
            };

            input?.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); consultar(); } });
            input?.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, 11);
                if (input.value.length === 11 && input.value !== ultimo) consultar();
            });
            btn.addEventListener('click', consultar);

            if (razon && btn.dataset.rucBuscar) sugerirContribuyentes(razon, btn.dataset.rucBuscar, completar);
        });
    }

    /** Lista de sugerencias bajo el campo de razón social (busca el RUC por nombre). */
    function sugerirContribuyentes(campo, url, alElegir) {
        const lista = document.createElement('div');
        lista.className = 'sugerencias';
        lista.setAttribute('role', 'listbox');
        lista.hidden = true;
        campo.parentElement.classList.add('position-relative');
        campo.insertAdjacentElement('afterend', lista);
        campo.setAttribute('autocomplete', 'off');
        campo.setAttribute('aria-autocomplete', 'list');

        let espera = null, control = null, resultados = [], activo = -1;
        const cerrar = () => { lista.hidden = true; activo = -1; };
        const marcar = (i) => {
            activo = i;
            $$('.sugerencias__item', lista).forEach((el, k) => el.classList.toggle('active', k === i));
        };
        const elegir = (i) => { const d = resultados[i]; if (!d) return; cerrar(); alElegir(d); };

        const pintar = (datos, aviso) => {
            resultados = datos;
            lista.replaceChildren();
            const titulo = document.createElement('div');
            titulo.className = 'sugerencias__titulo';
            titulo.textContent = aviso || (datos.length ? 'Seleccione el contribuyente para completar el RUC:' : 'Sin coincidencias. Puede escribir los datos manualmente.');
            lista.appendChild(titulo);
            datos.forEach((d, i) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'sugerencias__item';
                item.setAttribute('role', 'option');
                const nombre = document.createElement('span');
                nombre.className = 'sugerencias__nombre';
                nombre.textContent = d.razon_social;
                const detalle = document.createElement('span');
                detalle.className = 'sugerencias__detalle';
                detalle.textContent = ['RUC ' + d.ruc, d.estado, d.fuente].filter(Boolean).join(' · ');
                item.append(nombre, detalle);
                item.addEventListener('mousedown', (e) => e.preventDefault());
                item.addEventListener('click', () => elegir(i));
                lista.appendChild(item);
            });
            lista.hidden = false;
        };

        campo.addEventListener('input', () => {
            clearTimeout(espera);
            const q = campo.value.trim();
            if (q.length < 3) { cerrar(); return; }
            espera = setTimeout(async () => {
                control?.abort();
                control = new AbortController();
                pintar([], 'Buscando contribuyentes…');
                try {
                    const r = await fetch(`${url}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' }, signal: control.signal });
                    const d = await r.json();
                    if (document.activeElement === campo) pintar(d.resultados || [], d.mensaje);
                } catch (e) {
                    if (e.name !== 'AbortError') cerrar();
                }
            }, 400);
        });
        campo.addEventListener('keydown', (e) => {
            if (lista.hidden || !resultados.length) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); marcar(Math.min(activo + 1, resultados.length - 1)); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(Math.max(activo - 1, 0)); }
            else if (e.key === 'Enter' && activo >= 0) { e.preventDefault(); elegir(activo); }
            else if (e.key === 'Escape') { cerrar(); }
        });
        campo.addEventListener('blur', () => setTimeout(cerrar, 150));
    }

    /* ── Utilidades de formularios ─────────────────────────────────────── */
    function iniciarFormularios(root = document) {
        // Campos en mayúsculas (placas, series de comprobantes)
        $$('input[data-mayusculas]', root).forEach(el => el.addEventListener('input', () => {
            const pos = el.selectionStart;
            el.value = el.value.toUpperCase();
            try { el.setSelectionRange(pos, pos); } catch (e) { /* tipo no compatible */ }
        }));

        // Solo dígitos (DNI, RUC, SIAF)
        $$('input[data-solo-digitos]', root).forEach(el => el.addEventListener('input', () => {
            el.value = el.value.replace(/\D/g, '');
        }));

        // Contador de caracteres para áreas de texto con maxlength
        $$('textarea[maxlength][data-contador]', root).forEach(el => {
            const ayuda = document.createElement('div');
            ayuda.className = 'form-text text-end';
            el.insertAdjacentElement('afterend', ayuda);
            const actualizar = () => { ayuda.textContent = `${el.value.length} / ${el.maxLength} caracteres`; };
            el.addEventListener('input', actualizar);
            actualizar();
        });

        // Selectores de filtro que envían el formulario al cambiar
        $$('select[data-autoenviar]', root).forEach(el => el.addEventListener('change', () => el.form?.requestSubmit()));

        // Mostrar / ocultar contraseña
        $$('[data-ver-password]', root).forEach(btn => btn.addEventListener('click', () => {
            const input = $(btn.dataset.verPassword, root);
            if (!input) return;
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            btn.querySelector('i')?.classList.toggle('bi-eye', visible);
            btn.querySelector('i')?.classList.toggle('bi-eye-slash', !visible);
            btn.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        }));

        // Desplazar al primer campo con error
        const conError = $('.is-invalid', root);
        if (conError) { conError.scrollIntoView({ behavior: 'smooth', block: 'center' }); conError.focus({ preventScroll: true }); }
    }

    /* ── Ventana flotante para registrar, ver y editar ──────────────────── */
    // Uso: <a href="/alimentos/registros/create" data-modal [data-modal-tamano="md"]>
    // El servidor responde solo el contenido (plantilla layouts.modal) cuando recibe la
    // cabecera X-Modal. Dentro de la ventana, los enlaces data-modal cambian su contenido.
    function iniciarVentanaFlotante() {
        const modalEl = $('#modalFormulario');
        if (!modalEl) return;
        const contenido = $('.modal-content', modalEl);
        const dialogo = $('.modal-dialog', modalEl);
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        const cargando = `<div class="modal-body text-center py-5" role="status">
            <div class="spinner-border text-warning" style="width:3rem;height:3rem" aria-hidden="true"></div>
            <p class="mt-3 mb-0 fs-5">Cargando…</p></div>`;
        const error = (url) => `<div class="modal-header"><h2 class="modal-title">No se pudo abrir la ventana</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body"><div class="callout callout--danger"><i class="bi bi-x-octagon-fill" aria-hidden="true"></i>
            <div>Ocurrió un problema al cargar la información. Intente nuevamente.</div></div></div>
            <div class="modal-footer"><a class="btn btn-outline-secondary" href="${url}">Abrir como página</a>
            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button></div>`;

        async function abrir(url, tamano) {
            dialogo.classList.toggle('modal-xl', tamano !== 'md');
            dialogo.classList.toggle('modal-lg', tamano === 'md');
            contenido.innerHTML = cargando;
            if (!modalEl.classList.contains('show')) modal.show();
            try {
                const r = await fetch(url, { headers: { 'X-Modal': '1', 'Accept': 'text/html' }, credentials: 'same-origin' });
                if (r.redirected && new URL(r.url).pathname === '/login') { window.location.href = r.url; return; }
                if (!r.ok) throw new Error(String(r.status));
                const html = await r.text();
                if (/<html[\s>]/i.test(html)) { window.location.href = url; return; }
                contenido.innerHTML = html;
                ERP.inicializar(contenido);
                const primero = $('.modal-body input:not([type=hidden]):not([readonly]), .modal-body select, .modal-body textarea', contenido);
                (primero && !primero.closest('.sugerencias') ? primero : $('.btn-close', contenido))?.focus({ preventScroll: true });
            } catch (e) {
                contenido.innerHTML = error(url);
            }
        }
        ERP.abrirVentana = abrir;

        document.addEventListener('click', (e) => {
            const a = e.target.closest('a[data-modal]');
            if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
            e.preventDefault();
            abrir(a.href, a.dataset.modalTamano);
        });

        // «Cancelar» dentro de la ventana la cierra en lugar de cambiar de página.
        contenido.addEventListener('click', (e) => {
            const a = e.target.closest('.form-actions a.btn');
            if (a && !a.hasAttribute('data-modal')) { e.preventDefault(); modal.hide(); }
        });
        modalEl.addEventListener('hidden.bs.modal', () => { contenido.replaceChildren(); });

        // Ventanas apiladas (confirmar, pagar): al cerrarse, la página sigue bloqueada.
        ['#modalConfirmar', '#modalPago'].forEach(sel => $(sel)?.addEventListener('hidden.bs.modal', () => {
            if (modalEl.classList.contains('show')) {
                document.body.classList.add('modal-open');
                document.body.style.overflow = 'hidden';
            }
        }));
    }

    /* ── Envío en segundo plano (formularios de las ventanas flotantes) ── */
    function seEnviaEnSegundoPlano(form) {
        return (form.closest('#modalFormulario') || form.hasAttribute('data-ajax'))
            && (form.getAttribute('method') || 'get').toLowerCase() !== 'get';
    }

    function limpiarErrores(form) {
        $$('[data-error-js]', form).forEach(el => el.remove());
        $$('.is-invalid', form).forEach(el => el.classList.remove('is-invalid'));
    }

    function mostrarAvisoFormulario(form, titulo, lineas = []) {
        const aviso = document.createElement('div');
        aviso.className = 'callout callout--danger mb-3';
        aviso.setAttribute('role', 'alert');
        aviso.dataset.errorJs = '';
        aviso.innerHTML = '<i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i><div><div class="callout__title"></div><ul class="mb-0 mt-1 ps-3"></ul></div>';
        $('.callout__title', aviso).textContent = titulo;
        const ul = $('ul', aviso);
        lineas.slice(0, 8).forEach(t => { const li = document.createElement('li'); li.textContent = t; ul.appendChild(li); });
        if (!lineas.length) ul.remove();
        const destino = $('.card-body, .modal-body', form) || form;
        destino.prepend(aviso);
        aviso.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function mostrarErrores(form, errores) {
        let primero = null;
        Object.entries(errores || {}).forEach(([campo, mensajes]) => {
            const partes = campo.split('.');
            const nombre = partes[0] + partes.slice(1).map(p => `[${p}]`).join('');
            const el = form.querySelector(`[name="${nombre}"], [name="${partes[0]}[]"]`);
            if (!el) return;
            el.classList.add('is-invalid');
            if (!$(`[data-error-js][data-campo="${partes[0]}"]`, form)) {
                const msg = document.createElement('div');
                msg.className = 'invalid-feedback d-block';
                msg.dataset.errorJs = '';
                msg.dataset.campo = partes[0];
                msg.textContent = mensajes[0];
                const ancla = el.closest('.input-group') || (el.type === 'checkbox' ? el.closest('.row') : null) || el;
                ancla.insertAdjacentElement('afterend', msg);
            }
            primero ??= el;
        });
        return primero;
    }

    function iniciarEnvioEnSegundoPlano() {
        document.addEventListener('submit', async (e) => {
            const form = e.target;
            if (e.defaultPrevented || !seEnviaEnSegundoPlano(form)) return;
            e.preventDefault();
            if (form.dataset.enviando === '1') return;
            form.dataset.enviando = '1';
            limpiarErrores(form);

            const botones = $$('button[type="submit"], button:not([type])', form);
            botones.forEach(btn => {
                btn.disabled = true;
                btn.dataset.html ??= btn.innerHTML;
                if (btn === e.submitter || botones.length === 1) {
                    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${btn.dataset.textoEnvio || 'Guardando…'}`;
                }
            });
            const restaurar = () => {
                form.dataset.enviando = '';
                delete form.dataset.confirmado;
                botones.forEach(btn => { btn.disabled = false; if (btn.dataset.html) btn.innerHTML = btn.dataset.html; });
            };

            try {
                const r = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                    credentials: 'same-origin',
                });
                let d = null;
                try { d = await r.clone().json(); } catch (err) { /* respuesta HTML */ }

                if (r.ok && d && d.ok) {
                    const destino = d.redirect ? new URL(d.redirect, window.location.href) : null;
                    if (destino && destino.pathname !== window.location.pathname) window.location.href = destino.href;
                    else window.location.reload();
                    return;
                }
                if (r.ok && !d) { window.location.href = r.redirected ? r.url : window.location.href; return; }

                restaurar();
                if (r.status === 422 && d) {
                    const lista = Object.values(d.errors || {}).map(m => m[0]);
                    mostrarAvisoFormulario(form, 'Falta completar o corregir algunos datos:', lista);
                    mostrarErrores(form, d.errors)?.focus({ preventScroll: true });
                } else if (r.status === 419) {
                    mostrarAvisoFormulario(form, 'Su sesión expiró por inactividad. Recargue la página e inicie sesión nuevamente.');
                } else if (r.status === 403) {
                    mostrarAvisoFormulario(form, d?.message && d.message !== 'This action is unauthorized.' ? d.message : 'Su cuenta no tiene permiso para realizar esta acción.');
                } else {
                    mostrarAvisoFormulario(form, d?.message || 'No fue posible guardar la información. Intente nuevamente.');
                }
            } catch (err) {
                restaurar();
                mostrarAvisoFormulario(form, 'No hay conexión con el servidor. Verifique su conexión e intente nuevamente.');
            }
        });
    }

    /* ── Tooltips ──────────────────────────────────────────────────────── */
    function iniciarTooltips(root = document) {
        $$('[data-bs-toggle="tooltip"], [title][data-tooltip]', root).forEach(el => bootstrap.Tooltip.getOrCreateInstance(el));
    }

    /* ── Tema de los gráficos ──────────────────────────────────────────── */
    function iniciarGraficos() {
        if (!window.Chart) return;
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.font.size = 14;
        Chart.defaults.color = '#3d3d3d';
        Chart.defaults.borderColor = '#ece8dc';
        Chart.defaults.plugins.tooltip.backgroundColor = '#141414';
        Chart.defaults.plugins.tooltip.titleColor = '#F4C014';
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.callbacks.label = (ctx) => {
            const v = ctx.parsed?.y ?? ctx.parsed;
            return `${ctx.dataset.label || ctx.label}: ${soles(v)}`;
        };
    }
    // Colores del logotipo del INPE: dorado, negro, verde y rojo.
    window.ERP.colores = ['#E0AE0B', '#1F1F1F', '#0C9044', '#C1121F', '#8A6D0B', '#6E6E6E', '#F2D46B', '#4E8F3A', '#A3521F', '#B5B0A1'];

    /** Comportamientos que dependen del contenido (página o ventana flotante). */
    window.ERP.inicializar = function (root = document) {
        iniciarTotales(root);
        iniciarConsultaRuc(root);
        iniciarFormularios(root);
        iniciarTooltips(root);
    };

    document.addEventListener('DOMContentLoaded', () => {
        iniciarBarraLateral();
        iniciarToasts();
        iniciarConfirmaciones();
        iniciarEnvioEnSegundoPlano();   // antes que el envío único
        iniciarEnvioUnico();
        iniciarModalPago();
        iniciarVentanaFlotante();
        iniciarGraficos();
        ERP.inicializar(document);
    });
})();
