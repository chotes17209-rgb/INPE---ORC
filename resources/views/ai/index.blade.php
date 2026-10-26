@extends('layouts.app')
@section('title', 'Asistente inteligente')
@section('page-title', 'Asistente inteligente')
@section('page-subtitle', 'Consultas en lenguaje natural y análisis automáticos sobre la información registrada en el sistema.')
@section('breadcrumb')<li class="breadcrumb-item active" aria-current="page">Asistente inteligente</li>@endsection

@section('page-actions')
    <a href="{{ route('ia.resumen-ejecutivo-pdf') }}" class="btn btn-outline-primary" data-descarga-ia>
        <i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>Resumen ejecutivo (PDF)
    </a>
@endsection

@section('content')
@unless($iaConfigurada)
    <div class="callout callout--warning mb-3"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        <div><span class="callout__title">Servicio no configurado.</span> Registre la clave del proveedor de IA en la variable <code>IA_API_KEY</code> del archivo <code>.env</code> para habilitar el asistente.</div></div>
@endunless

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card ia-wrapper">
            <div class="card-header">
                <span class="ia-avatar bot" aria-hidden="true"><i class="bi bi-stars"></i></span>
                <span>
                    <span class="d-block">Asistente del {{ config('inpe.area') }}</span>
                    <span class="d-block small text-muted fw-normal">Responde con base en los datos registrados en el sistema</span>
                </span>
                <span class="card-header__aside">
                    <x-estado :color="$iaConfigurada ? 'success' : 'secondary'">{{ $iaConfigurada ? 'Disponible' : 'No configurado' }}</x-estado>
                    <button type="button" class="btn btn-sm btn-light btn-icon" id="btnLimpiar" title="Nueva conversación" aria-label="Iniciar una nueva conversación"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
                </span>
            </div>

            <div class="ia-chat" id="chat" aria-live="polite" aria-label="Conversación con el asistente"></div>

            <div class="ia-input">
                <div class="ia-sugerencias" aria-label="Consultas sugeridas">
                    @foreach([
                        '¿Qué vehículos tienen el SOAT vencido o por vencer en los próximos 30 días?',
                        '¿Qué operaciones recíprocas están vencidas y cuál es el monto total?',
                        '¿Cuánto se ha pagado en alimentos este año y con qué proveedores?',
                        '¿Qué acciones prioritarias debo atender hoy?',
                    ] as $sugerencia)
                        <button type="button" class="ia-sugerencia" data-pregunta="{{ $sugerencia }}">{{ $sugerencia }}</button>
                    @endforeach
                </div>
                <form id="formPregunta" class="d-flex gap-2" data-no-bloquear>
                    <label for="pregunta" class="visually-hidden">Escriba su consulta</label>
                    <input type="text" id="pregunta" class="form-control" maxlength="500" autocomplete="off"
                           placeholder="Escriba su consulta, por ejemplo: ¿cuánto se gastó en mantenimiento este año?" @disabled(! $iaConfigurada)>
                    <button type="submit" class="btn btn-primary px-3" id="btnEnviar" aria-label="Enviar consulta" @disabled(! $iaConfigurada)><i class="bi bi-send" aria-hidden="true"></i></button>
                </form>
                <div class="form-text">Las respuestas se generan automáticamente y tienen carácter referencial. Verifique la información antes de tomar decisiones.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-lightning-charge card-header__icon" aria-hidden="true"></i> Análisis especializados</div>
            <div class="card-body d-grid gap-2">
                <button type="button" class="btn btn-light text-start" data-analisis="predecir" @disabled(! $iaConfigurada)>
                    <i class="bi bi-truck-front me-2 text-primary" aria-hidden="true"></i>Predicción de mantenimiento de la flota
                </button>
                <button type="button" class="btn btn-light text-start" data-analisis="alertas" @disabled(! $iaConfigurada)>
                    <i class="bi bi-bell me-2 text-primary" aria-hidden="true"></i>Alertas de vencimientos y gasto
                </button>
                <div class="border rounded p-2">
                    <label for="selectVehiculo" class="form-label small mb-1">Análisis de un vehículo</label>
                    <div class="d-flex gap-2">
                        <select id="selectVehiculo" class="form-select form-select-sm" @disabled(! $iaConfigurada)>
                            <option value="">Seleccione…</option>
                            @foreach($vehiculos as $v)
                                <option value="{{ $v->id }}">{{ $v->placa }} — {{ $v->marca }} {{ $v->modelo }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-sm btn-primary" data-analisis="vehiculo" @disabled(! $iaConfigurada)>Analizar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-activity card-header__icon" aria-hidden="true"></i> Situación actual</div>
            <div class="card-body py-2">
                <dl class="lista-definicion">
                    <div><dt>Vehículos registrados</dt><dd>{{ $statsParque['total'] }}</dd></div>
                    <div><dt>Disponibles para servicio</dt><dd class="text-success">{{ $statsParque['operativos'] }}</dd></div>
                    <div><dt>En mantenimiento</dt><dd>{{ $statsParque['mantenimiento'] }}</dd></div>
                    <div><dt>Gasto en mantenimiento {{ date('Y') }}</dt><dd class="money">S/ {{ number_format($statsParque['costo_mant_anio'], 2) }}</dd></div>
                    <div><dt>Alimentos pagados {{ date('Y') }}</dt><dd class="money">S/ {{ number_format($statsAlimentos['pagado'], 2) }}</dd></div>
                    <div><dt>Registros de alimentos vencidos</dt><dd class="{{ $statsAlimentos['vencidos'] ? 'text-danger' : '' }}">{{ $statsAlimentos['vencidos'] }}</dd></div>
                    <div><dt>Operaciones por pagar</dt><dd class="money">S/ {{ number_format($statsOperaciones['pendiente'], 2) }}</dd></div>
                    <div><dt>Operaciones vencidas</dt><dd class="money {{ $statsOperaciones['vencido'] > 0 ? 'text-danger' : '' }}">S/ {{ number_format($statsOperaciones['vencido'], 2) }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-shield-check card-header__icon" aria-hidden="true"></i> Acerca del asistente</div>
            <div class="card-body small text-muted">
                <p class="mb-2">El asistente utiliza un modelo de lenguaje (<span class="font-monospace">{{ $modeloIA }}</span>) al que se envía un resumen de los registros del sistema en cada consulta.</p>
                <p class="mb-0">No registra ni modifica información; únicamente la consulta y la analiza.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const chat = document.getElementById('chat');
    const input = document.getElementById('pregunta');
    const btnEnviar = document.getElementById('btnEnviar');
    const rutas = {
        consultar: @json(route('ia.consultar')),
        predecir:  @json(route('ia.predecir-mantenimiento')),
        alertas:   @json(route('ia.alertas-operaciones')),
        vehiculo:  @json(route('ia.analizar-vehiculo')),
    };
    const bienvenida = 'Buen día. Puedo ayudarle a consultar y analizar la información del parque automotor, el suministro de alimentos y las operaciones recíprocas. Escriba su consulta o elija una de las sugerencias.';

    /** Convierte el texto de la IA (markdown simple) en nodos seguros, sin insertar HTML externo. */
    function renderizar(texto) {
        const cont = document.createDocumentFragment();
        let lista = null;
        const enLinea = (linea, destino) => {
            linea.split(/(\*\*[^*]+\*\*)/g).forEach(parte => {
                if (/^\*\*[^*]+\*\*$/.test(parte)) {
                    const b = document.createElement('strong'); b.textContent = parte.slice(2, -2); destino.appendChild(b);
                } else if (parte) {
                    destino.appendChild(document.createTextNode(parte));
                }
            });
        };
        texto.split('\n').forEach(linea => {
            const l = linea.trim();
            if (!l) { lista = null; return; }
            const titulo = l.match(/^#{1,4}\s+(.*)$/);
            const vineta = l.match(/^(?:[-*•]|\d+[.)])\s+(.*)$/);
            if (titulo) {
                lista = null;
                const h = document.createElement('h5'); enLinea(titulo[1], h); cont.appendChild(h);
            } else if (vineta) {
                if (!lista) { lista = document.createElement('ul'); cont.appendChild(lista); }
                const li = document.createElement('li'); enLinea(vineta[1], li); lista.appendChild(li);
            } else {
                lista = null;
                const p = document.createElement('p'); enLinea(l, p); cont.appendChild(p);
            }
        });
        return cont;
    }

    function agregar(texto, tipo) {
        const fila = document.createElement('div');
        fila.className = 'ia-msg ' + tipo;
        const avatar = document.createElement('span');
        avatar.className = 'ia-avatar ' + tipo;
        avatar.setAttribute('aria-hidden', 'true');
        avatar.innerHTML = tipo === 'bot' ? '<i class="bi bi-stars"></i>' : '<i class="bi bi-person"></i>';
        const burbuja = document.createElement('div');
        burbuja.className = 'ia-bubble';
        if (tipo === 'bot') burbuja.appendChild(renderizar(texto)); else burbuja.textContent = texto;
        fila.append(avatar, burbuja);
        chat.appendChild(fila);
        chat.scrollTop = chat.scrollHeight;
        return fila;
    }

    function escribiendo() {
        const fila = agregar('', 'bot');
        fila.querySelector('.ia-bubble').innerHTML = '<div class="ia-typing" aria-label="El asistente está escribiendo"><span></span><span></span><span></span></div>';
        return fila;
    }

    async function solicitar(url, datos, campo, mensajeUsuario) {
        agregar(mensajeUsuario, 'user');
        const indicador = escribiendo();
        btnEnviar.disabled = true;
        try {
            const r = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': ERP.csrf() },
                body: JSON.stringify(datos)
            });
            if (r.status === 429) throw new Error('Se alcanzó el límite de consultas por minuto. Espere un momento.');
            if (!r.ok) throw new Error('El servidor no pudo procesar la consulta.');
            const d = await r.json();
            indicador.remove();
            agregar(d[campo] || 'No se obtuvo respuesta.', 'bot');
        } catch (e) {
            indicador.remove();
            agregar(e.message || 'No fue posible conectar con el servicio de IA.', 'bot');
        } finally {
            btnEnviar.disabled = false;
            input.focus();
        }
    }

    document.getElementById('formPregunta').addEventListener('submit', (e) => {
        e.preventDefault();
        const pregunta = input.value.trim();
        if (!pregunta) return;
        input.value = '';
        solicitar(rutas.consultar, { pregunta }, 'respuesta', pregunta);
    });

    document.querySelectorAll('[data-pregunta]').forEach(btn => btn.addEventListener('click', () => {
        if (btnEnviar.disabled) return;
        solicitar(rutas.consultar, { pregunta: btn.dataset.pregunta }, 'respuesta', btn.dataset.pregunta);
    }));

    document.querySelectorAll('[data-analisis]').forEach(btn => btn.addEventListener('click', () => {
        const tipo = btn.dataset.analisis;
        if (tipo === 'predecir') solicitar(rutas.predecir, {}, 'prediccion', 'Predicción de mantenimiento de la flota');
        if (tipo === 'alertas') solicitar(rutas.alertas, {}, 'alertas', 'Alertas de vencimientos y gasto');
        if (tipo === 'vehiculo') {
            const sel = document.getElementById('selectVehiculo');
            if (!sel.value) { ERP.notificar('Seleccione un vehículo para analizar.', 'warning'); sel.focus(); return; }
            solicitar(rutas.vehiculo, { vehiculo_id: sel.value }, 'analisis', 'Análisis del vehículo ' + sel.options[sel.selectedIndex].text);
        }
    }));

    document.getElementById('btnLimpiar').addEventListener('click', () => { chat.replaceChildren(); agregar(bienvenida, 'bot'); });

    document.querySelector('[data-descarga-ia]')?.addEventListener('click', () => {
        ERP.notificar('Generando el resumen ejecutivo. La descarga puede tardar unos segundos.', 'info');
    });

    agregar(bienvenida, 'bot');
})();
</script>
@endpush
