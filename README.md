# Sistema Integrado de Gestión Administrativa — INPE Oficina Regional Centro

> **Tesis:** *Implementación de un Sistema ERP con Inteligencia Artificial para la Gestión Integral del Área Administrativa de la Oficina Regional Centro del Instituto Nacional Penitenciario (INPE ORC).*

Aplicación web desarrollada en **Laravel 12** que integra en una sola plataforma los procesos del Área Administrativa de la Oficina Regional Centro del INPE: control del parque automotor, suministro de alimentos a los establecimientos penitenciarios y operaciones recíprocas (servicios básicos, contratos y convenios), con reportes institucionales y un asistente de inteligencia artificial.

---

## 1. Módulos

| Módulo | Alcance |
|---|---|
| **Panel de control** | Indicadores del ejercicio, alertas de vencimientos, gasto mensual, ejecución por proveedor y accesos directos. |
| **Parque automotor** | Inventario vehicular, control de SOAT, revisión técnica y seguro, historial de mantenimientos, cambio de estado y ficha del vehículo. |
| **Mantenimientos** | Programación (preventivo, correctivo, emergencia), orden de servicio, SIAF, comprobante y registro de conformidad con actualización del kilometraje. |
| **Suministro de alimentos** | Comprobantes por establecimiento penal, periodo de suministro, contrato, garantía, O.S./SIAF, registro de pago y resumen mensual. |
| **Operaciones recíprocas** | Servicios básicos y otros pagos a entidades, vencimientos, periodo de consumo, registro de pago y resumen mensual. |
| **Reportes** | Reporte general del ejercicio, ejecución del gasto por módulo, reportes PDF con membrete institucional y exportación a Excel (CSV). |
| **Asistente inteligente** | Consultas en lenguaje natural sobre los datos del sistema, predicción de mantenimientos, alertas de gasto y resumen ejecutivo en PDF. |
| **Administración** | Usuarios, roles, matriz de permisos y registro de actividad (auditoría). |

## 2. Roles y permisos

El control de acceso se basa en roles (*Spatie Laravel Permission*) y en permisos definidos con *Gates* en `app/Providers/AppServiceProvider.php`.

| Acción | Superadministrador | Administrador | Contador | Asistente | Visualizador |
|---|:-:|:-:|:-:|:-:|:-:|
| Consultar información y reportes | ✔ | ✔ | ✔ | ✔ | ✔ |
| Registrar y editar registros | ✔ | ✔ | ✔ | ✔ | |
| Registrar pagos, dar conformidad y eliminar | ✔ | ✔ | ✔ | | |
| Administrar usuarios y roles | ✔ | ✔ | | | |

Las rutas están protegidas con el middleware `can:` y la interfaz oculta las acciones no autorizadas. Solo un superadministrador puede gestionar cuentas de superadministrador, y los roles del sistema no pueden renombrarse ni eliminarse.

## 3. Requisitos

- PHP 8.2 o superior con las extensiones `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd`, `fileinfo`
- MySQL 8.0 o MariaDB 10.6+
- Composer 2.x
- (Opcional) Clave de un proveedor de IA compatible con la API de OpenAI (Groq, OpenAI)
- (Opcional) Token de [apis.net.pe](https://apis.net.pe) para la consulta de RUC

## 4. Instalación

```bash
composer install
cp .env.example .env          # en Windows: copy .env.example .env
php artisan key:generate
```

Configure la base de datos en `.env` y cree la base de datos:

```sql
CREATE DATABASE erp_inpe_orc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Ingrese a <http://localhost:8000>. Para una guía paso a paso con XAMPP, consulte [`INSTALACION.md`](INSTALACION.md).

### Usuarios de prueba (creados por el *seeder*)

| Rol | Correo | Contraseña |
|---|---|---|
| Superadministrador | admin@inpe-orc.gob.pe | Admin2024* |
| Contador | contador@inpe-orc.gob.pe | Contador2024* |
| Asistente | asistente@inpe-orc.gob.pe | Asistente2024* |

> Cambie estas contraseñas antes de usar el sistema con información real.

## 5. Configuración

| Variable | Uso |
|---|---|
| `INPE_*` | Datos institucionales que aparecen en la interfaz y en los reportes PDF. |
| `IA_API_KEY`, `IA_MODEL`, `IA_BASE_URL` | Proveedor del asistente inteligente. Se mantiene la compatibilidad con `OPENAI_API_KEY` y `OPENAI_MODEL`. |
| `JSONPE_TOKEN` | Consulta de contribuyentes en json.pe en ambos sentidos: RUC → razón social y razón social → RUC. |
| `APIPERU_TOKEN`, `APIPERU_URL` | Alternativa para RUC → razón social (decolecta o apis.net.pe). |

La consulta de RUC se hace desde el servidor, por lo que los tokens no se exponen en el navegador. Sin token, la búsqueda por nombre sugiere los proveedores ya registrados en el sistema.

Los días de anticipación de las alertas se configuran en `config/inpe.php`.

### Despliegue en Render

El repositorio incluye un `Dockerfile` (PHP 8.3 + Apache) y un *Blueprint* (`render.yaml`) que crea el servicio web y la base de datos PostgreSQL ya conectados.

1. En Render: **New → Blueprint** y seleccione este repositorio.
2. Render crea el servicio `inpe-orc` (Docker) y la base de datos `inpe-orc-db` (PostgreSQL), genera `APP_KEY` y enlaza `DB_URL`.
3. Opcional: registre `IA_API_KEY` y `APIPERU_TOKEN` en **Environment**.

En cada despliegue se ejecutan las migraciones y los datos iniciales (variable `SEED_ON_DEPLOY`, que no duplica registros). Si crea el servicio manualmente, elija el *runtime* **Docker** (no Node.js) y defina `APP_KEY`, `DB_CONNECTION=pgsql` y `DB_URL` con la *Internal Database URL* de la base de datos.

> La base de datos gratuita de Render se elimina a los 30 días; para uso continuo elija un plan de pago.

### Tarea programada

El comando `operaciones:verificar-vencimientos` actualiza los estados vencidos y genera notificaciones de pagos y documentos vehiculares próximos a vencer. Se ejecuta todos los días a las 07:00 si el programador de Laravel está activo:

```bash
php artisan schedule:work            # desarrollo
# Producción (cron):  * * * * * php /ruta/al/proyecto/artisan schedule:run >> /dev/null 2>&1
```

## 6. Arquitectura

```
app/
├── Console/Commands/          Tarea diaria de vencimientos y alertas
├── Http/
│   ├── Controllers/
│   │   ├── Admin/             Usuarios y roles
│   │   ├── AI/                Asistente inteligente
│   │   ├── Auth/              Inicio de sesión (con bloqueo por intentos fallidos)
│   │   ├── Administrativo/    Parque automotor, mantenimientos, alimentos, operaciones y reportes
│   │   └── Servicios/         Consulta de RUC
│   ├── Middleware/            Verificación de cuenta activa
│   └── Requests/              Reglas de validación de cada formulario
├── Models/                    Modelos Eloquent y auditoría (Concerns/RegistraAuditoria)
├── Services/AI/               Cliente del servicio de IA
└── Support/                   Catálogos compartidos y exportación CSV
config/inpe.php                Datos institucionales y plazos de alerta
lang/es/                       Mensajes del sistema y de validación en español
public/css/erp-inpe.css        Hoja de estilos institucional
public/js/erp-inpe.js          Comportamientos comunes de la interfaz
resources/views/
├── components/                Componentes Blade (indicadores, estados, fichas, secciones…)
├── layouts/                   Plantilla principal, menú lateral y barra superior
├── partials/                  Modales compartidos (confirmación y registro de pago)
├── reportes/layouts/pdf       Plantilla institucional de los PDF
└── errors/                    Páginas de error en español
```

### Base de datos

| Tabla | Descripción |
|---|---|
| `users`, `roles`, `permissions`, `model_has_roles`… | Usuarios y control de acceso |
| `vehiculos` | Inventario del parque automotor |
| `vehiculo_mantenimientos` | Mantenimientos programados y ejecutados |
| `alimentos` | Comprobantes del suministro de alimentos |
| `operaciones_reciprocas` | Comprobantes de servicios básicos, contratos y convenios |
| `notificaciones_sistema` | Alertas internas por usuario |
| `audit_logs` | Registro de creación, modificación y eliminación de datos |

## 7. Seguridad

- Autenticación por sesión con regeneración del identificador y bloqueo temporal tras 5 intentos fallidos.
- Control de acceso por roles en rutas (`can:`) y en la interfaz.
- Validación en el servidor mediante *Form Requests*, con mensajes en español.
- Protección CSRF en todos los formularios y confirmación previa de las acciones irreversibles.
- Auditoría automática de cambios (usuario, IP, valores anteriores y nuevos).
- Eliminación lógica (*soft delete*) en los registros administrativos.
- Claves de servicios externos solo en el servidor (`.env`), nunca en el navegador.
- Las respuestas del asistente de IA se muestran como texto, sin interpretar HTML.

## 8. Tecnologías

Laravel 12 · PHP 8.2 · MySQL 8 · Bootstrap 5.3 · Bootstrap Icons · Chart.js 4 · DomPDF · Spatie Laravel Permission · API compatible con OpenAI (Groq / LLaMA 3.3).

## 9. Referencias

1. Pressman, R. S. y Maxim, B. R. (2020). *Ingeniería del software: un enfoque práctico* (9.ª ed.). McGraw-Hill.
2. Laudon, K. C. y Laudon, J. P. (2020). *Sistemas de información gerencial* (16.ª ed.). Pearson.
3. Russell, S. y Norvig, P. (2021). *Artificial Intelligence: A Modern Approach* (4.ª ed.). Pearson.
4. Ley N.° 29151, Ley General del Sistema Nacional de Bienes Estatales.
5. Directiva N.° 005-2016-EF/51.01, Metodología para el reconocimiento, medición, registro y presentación de los elementos de propiedades, planta y equipo de las entidades gubernamentales.

---

Desarrollado como parte de la tesis para optar el grado de Bachiller en Ingeniería de Sistemas. El historial de cambios de la versión 3.0 se describe en [`docs/CAMBIOS.md`](docs/CAMBIOS.md).
