#!/bin/sh
# Prepara Laravel antes de iniciar Apache (se ejecuta en cada despliegue).
set -e

# Render indica el puerto en la variable PORT (por defecto 10000).
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Clave de la aplicación: acepta una clave "base64:..." o la generada por Render.
if [ -z "${APP_KEY}" ]; then
    echo "ERROR: defina la variable APP_KEY en Render (Environment)." >&2
    exit 1
fi
case "${APP_KEY}" in
    base64:*) ;;
    *) export APP_KEY="base64:${APP_KEY}" ;;
esac

# URL pública que asigna Render, si no se definió APP_URL.
if [ -z "${APP_URL}" ] && [ -n "${RENDER_EXTERNAL_URL}" ]; then
    export APP_URL="${RENDER_EXTERNAL_URL}"
fi

php artisan package:discover --ansi
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache

# Crea o actualiza las tablas de la base de datos.
php artisan migrate --force

# Datos iniciales (roles y usuarios de prueba). Es seguro repetirlo: no duplica registros.
if [ "${SEED_ON_DEPLOY:-true}" = "true" ]; then
    php artisan db:seed --force
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
