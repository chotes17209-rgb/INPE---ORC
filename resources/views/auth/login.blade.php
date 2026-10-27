<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Iniciar sesión · {{ config('inpe.siglas') }} {{ config('inpe.oficina') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/erp-inpe.css') }}?v={{ config('inpe.version') }}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
</head>
<body>
<div class="login-page">
    <section class="login-aside" aria-label="Presentación del sistema">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ asset('img/inpe-escudo.png') }}" alt="" class="login-escudo" width="92" height="102">
            <div>
                <div class="small text-uppercase" style="letter-spacing:.08em;color:#D9C27A">{{ config('inpe.sector') }}</div>
                <div class="fw-semibold text-white">{{ config('inpe.institucion') }}</div>
            </div>
        </div>

        <div style="max-width:470px">
            <h1>{{ config('inpe.sistema') }}</h1>
            <p class="mb-0">{{ config('inpe.oficina') }} — {{ config('inpe.area') }}</p>
            <ul class="login-aside__lista">
                <li><i class="bi bi-truck-front" aria-hidden="true"></i><span>Control del parque automotor, documentos y mantenimientos.</span></li>
                <li><i class="bi bi-basket3" aria-hidden="true"></i><span>Seguimiento de contratos y pagos del suministro de alimentos.</span></li>
                <li><i class="bi bi-receipt" aria-hidden="true"></i><span>Registro de operaciones recíprocas y servicios básicos.</span></li>
                <li><i class="bi bi-stars" aria-hidden="true"></i><span>Alertas y análisis asistidos por inteligencia artificial.</span></li>
            </ul>
        </div>

        <div class="small" style="color:#B5AE9C">
            Uso exclusivo del personal autorizado. Las acciones realizadas en el sistema quedan registradas.
        </div>
    </section>

    <section class="login-form-wrap">
        <div class="login-form">
            <img src="{{ asset('img/inpe-logo.png') }}" alt="{{ config('inpe.institucion') }} — INPE" class="login-logo" width="454" height="145">
            <p class="text-center text-muted mb-4">{{ config('inpe.oficina') }} · {{ config('inpe.area') }}</p>

            <h2 class="mb-1">Iniciar sesión</h2>
            <p class="text-muted mb-4">Ingrese con su cuenta institucional.</p>

            @if(session('info'))
                <div class="callout callout--info mb-3" role="status"><i class="bi bi-info-circle-fill" aria-hidden="true"></i><div>{{ session('info') }}</div></div>
            @endif
            @if(session('error'))
                <div class="callout callout--danger mb-3" role="alert"><i class="bi bi-x-octagon-fill" aria-hidden="true"></i><div>{{ session('error') }}</div></div>
            @endif
            @if($errors->any())
                <div class="callout callout--danger mb-3" role="alert"><i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i><div>{{ $errors->first() }}</div></div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope" aria-hidden="true"></i></span>
                        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" placeholder="usuario@inpe-orc.gob.pe" required autofocus autocomplete="username">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock" aria-hidden="true"></i></span>
                        <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary" data-ver-password="#password" aria-label="Mostrar contraseña">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">Mantener la sesión iniciada en este equipo</label>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100" data-texto-envio="Verificando…">
                    <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Ingresar
                </button>
            </form>

            <p class="small text-muted mt-4 mb-0 text-center">
                ¿Olvidó su contraseña? Comuníquese con el administrador del sistema.
            </p>
            <p class="small text-muted mt-4 mb-0 text-center">
                {{ config('inpe.sistema') }} v{{ config('inpe.version') }} · &copy; {{ date('Y') }}
            </p>
        </div>
    </section>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/erp-inpe.js') }}?v={{ config('inpe.version') }}"></script>
</body>
</html>
