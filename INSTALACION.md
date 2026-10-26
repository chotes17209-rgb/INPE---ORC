# Guía de instalación (Windows con XAMPP)

## 1. Requisitos previos

- XAMPP con PHP 8.2 o superior y MySQL/MariaDB.
- Composer 2.x (<https://getcomposer.org>).
- En `C:\xampp\php\php.ini`, verifique que estén habilitadas (sin `;` al inicio) las extensiones `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo_mysql` y `zip`.

## 2. Copiar el proyecto

Descomprima el proyecto en `C:\xampp\htdocs\erp-inpe-orc-nuevo\`.

## 3. Instalar dependencias

```bash
cd C:\xampp\htdocs\erp-inpe-orc-nuevo
composer install
```

## 4. Configurar el entorno

```bash
copy .env.example .env
php artisan key:generate
```

Edite `.env` y complete como mínimo:

```env
DB_DATABASE=erp_inpe_orc
DB_USERNAME=root
DB_PASSWORD=

IA_API_KEY=         # opcional: clave de Groq u OpenAI para el asistente
APIPERU_TOKEN=      # opcional: token de apis.net.pe para consultar RUC
```

> Si ya tenía un `.env` de la versión anterior, puede conservarlo: las variables `OPENAI_API_KEY` y `OPENAI_MODEL` siguen funcionando.

## 5. Crear la base de datos

En phpMyAdmin cree la base de datos `erp_inpe_orc` con cotejamiento `utf8mb4_unicode_ci`.

## 6. Crear las tablas y los datos iniciales

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

## 7. Iniciar el sistema

```bash
php artisan serve
```

Ingrese a <http://localhost:8000> con alguno de los usuarios de prueba:

| Rol | Correo | Contraseña |
|---|---|---|
| Superadministrador | admin@inpe-orc.gob.pe | Admin2024* |
| Contador | contador@inpe-orc.gob.pe | Contador2024* |
| Asistente | asistente@inpe-orc.gob.pe | Asistente2024* |

## 8. Alertas automáticas (opcional)

Para que las alertas de vencimiento se generen todos los días, deje abierta una consola con:

```bash
php artisan schedule:work
```

## Solución de problemas

| Problema | Solución |
|---|---|
| `Class "Spatie\Permission\..." not found` | Ejecute `composer install`. |
| `SQLSTATE[HY000] [1045] Access denied` | Revise `DB_USERNAME` y `DB_PASSWORD` en `.env`. |
| Los cambios no se ven en pantalla | Ejecute `php artisan optimize:clear`. |
| Las imágenes o adjuntos no se muestran | Ejecute `php artisan storage:link`. |
| Error 500 sin detalle | Ponga `APP_DEBUG=true` y revise `storage/logs/laravel.log`. |
| El asistente indica «no configurado» | Registre `IA_API_KEY` en `.env` y ejecute `php artisan config:clear`. |
