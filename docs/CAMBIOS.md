# Versión 3.0 — Análisis y mejoras

Este documento resume el análisis del sistema (versión 2.0) y los cambios realizados en la versión 3.0, cuyo objetivo fue hacerlo **más formal, institucional e intuitivo**, además de corregir errores y reforzar la seguridad.

## 1. Errores corregidos

| # | Problema encontrado | Corrección |
|---|---|---|
| 1 | **Balance presupuestal** fallaba: la vista usaba `$ingresos` y `$egresos`, que el controlador no enviaba, y hacía referencia al módulo de donaciones, ya eliminado. | Se rediseñó como **Ejecución del gasto**: compara por módulo lo registrado, lo pagado, el saldo y lo vencido, con su porcentaje de avance. |
| 2 | Al programar un mantenimiento, los datos de **orden de servicio, SIAF, total y factura** se ingresaban en el formulario pero **no se guardaban** (el controlador no los validaba). | Validación centralizada en `MantenimientoRequest`; ahora se guardan todos los campos. |
| 3 | Al completar un mantenimiento sin indicar kilometraje se producía un error (`Undefined array key`) y el vehículo quedaba en estado «mantenimiento». | El vehículo vuelve a «operativo» cuando no le quedan mantenimientos abiertos; el kilometraje no puede ser menor al registrado. |
| 4 | El campo **usuario del servicio** de operaciones recíprocas no se guardaba. | Incluido en la validación (como «código de suministro»), junto con el domicilio fiscal. |
| 5 | `ProveedorController` usaba el modelo `Donante`, que no existe. | Se eliminó el controlador sin uso. |
| 6 | La **consulta de RUC** se hacía desde el navegador directamente a la API externa, sin token, por lo que fallaba. Además existían dos rutas públicas (sin autenticación) que exponían el token. | Nuevo `ConsultaRucController`, protegido por inicio de sesión, con caché de 24 horas y límite de solicitudes. |
| 7 | Los **comprobantes PDF** usaban `display: flex`, que DomPDF no interpreta, por lo que salían descuadrados. | Nueva plantilla PDF institucional basada en tablas, con membrete, numeración de páginas y firmas. |
| 8 | El enlace «Programar mantenimiento» desde la ficha del vehículo usaba un parámetro distinto (`vehiculo`) al del listado (`vehiculo_id`), por lo que no preseleccionaba el vehículo. | Parámetro unificado. |
| 9 | Las notificaciones no podían marcarse como leídas. | Nuevo `NotificacionController`: al abrir una notificación se marca como leída y existe la opción «Marcar todas como leídas». |
| 10 | La carpeta `lang/` no existía: los mensajes de validación sin texto propio se mostraban en inglés. | Se agregaron `lang/es/*.php` y `lang/es.json`, con los nombres legibles de los campos. |
| 11 | Al editar alimentos u operaciones no se podía reemplazar el archivo adjunto, y los adjuntos no se mostraban. | Se puede adjuntar, reemplazar y consultar el documento sustentatorio. |
| 12 | La imagen del vehículo se subía pero no se mostraba. | Se muestra en la ficha del vehículo. |

## 2. Seguridad

- **Control de acceso por roles:** antes, cualquier usuario autenticado (incluido el visualizador) podía crear, editar, eliminar y registrar pagos. Ahora existen los permisos `registrar`, `aprobar` y `administrar`, aplicados en las rutas y en la interfaz.
- Solo un superadministrador puede crear o modificar superadministradores. Los roles del sistema no pueden renombrarse ni eliminarse, porque los permisos dependen de ellos.
- Un usuario no puede desactivarse, eliminarse ni quitarse roles a sí mismo.
- **Bloqueo por intentos fallidos** de inicio de sesión (5 intentos por correo e IP).
- **Auditoría real:** la tabla `audit_logs` existía pero no se usaba. Ahora el trait `RegistraAuditoria` registra automáticamente cada creación, modificación y eliminación, con el usuario, la IP y los valores anteriores y nuevos. La actividad se muestra en la ficha de cada usuario.
- Las respuestas del asistente de IA se insertaban como HTML (`innerHTML`), lo que permitía inyectar código. Ahora se muestran como texto con formato seguro.
- Las claves de servicios externos se leen desde `config/services.php`, no con `env()` en el código.
- Se agregaron los archivos `.gitignore` de `storage/` y `bootstrap/cache/` para no publicar sesiones, registros ni cachés.

## 3. Interfaz más formal e intuitiva

- **Identidad institucional:** paleta azul institucional con acentos rojo y dorado, tipografía *Public Sans* y franja institucional en la barra superior. Se eliminaron los emoticonos y los degradados llamativos.
- **Lenguaje formal y uniforme:** títulos, botones, mensajes y estados redactados en español formal («Registrar pago», «Registrar conformidad», «Situación del trámite», «Obligaciones por pagar»).
- **Encabezado de página uniforme:** título, descripción y acciones principales en el mismo lugar en todas las pantallas.
- **Formularios por secciones numeradas** (1. Proveedor, 2. Periodo, 3. Orden de servicio…), con textos de ayuda, campos obligatorios marcados y botones fijos al pie.
- **Un solo formulario por módulo** (`_form.blade.php`) compartido entre «crear» y «editar»: antes eran copias que ya mostraban diferencias.
- **Ayudas de captura:** cálculo automático del total, botón «18 %» para el IGV, consulta de RUC con «Enter», mayúsculas automáticas en placas y series, solo dígitos en DNI, RUC y SIAF, y contador de caracteres.
- **Confirmación formal** antes de eliminar, desactivar o dar conformidad, mediante un modal en lugar del cuadro `confirm()` del navegador.
- **Protección contra doble envío:** el botón se desactiva y muestra «Guardando…».
- **Modal único de registro de pago:** antes se generaba un modal por cada fila de la tabla.
- **Fichas de detalle** con seguimiento del trámite (registro → pago), alertas contextuales (vencido, observado, documentos vencidos) y acceso al documento sustentatorio.
- **Listados** con indicadores clicables, filtros que se aplican al seleccionar, botón para quitar filtros, subtotal por página y mensajes claros cuando no hay resultados.
- **Panel de control** reorganizado: asuntos que requieren atención, indicadores del ejercicio, distribución del gasto, vencimientos y mantenimientos de la semana.
- **Páginas de error** (403, 404, 419, 429, 500, 503) con mensajes institucionales.
- **Accesibilidad:** enlace para saltar al contenido, etiquetas asociadas a cada campo, `aria-label` en botones de solo icono y diseño adaptable a celulares.

## 4. Reportes

- Plantilla PDF común con el membrete del Estado peruano (PERÚ | Ministerio | INPE | Oficina Regional), código de documento, usuario emisor, numeración «Página X de Y» y espacios para firmas.
- Bloque de resumen (cantidad, total, pagado, por pagar, vencido) en cada reporte.
- Los reportes PDF y Excel respetan los filtros aplicados en pantalla.
- Archivos Excel (CSV) con cabecera institucional, importes sin separador de miles (Excel los reconoce como números) y fila de totales.
- El reporte general en Excel consolida los egresos de todos los módulos en una sola tabla.

## 5. Organización del código

| Antes | Ahora |
|---|---|
| Reglas de validación repetidas en `store()` y `update()` | Clases `Form Request` en `app/Http/Requests` |
| Catálogos (estados, tipos, métodos de pago) escritos a mano en cada vista | Constantes en los modelos y en `App\Support\Catalogo` |
| Marcado de «vencidos» repetido en controladores | `OperacionReciproca::actualizarVencidas()` y `Alimento::actualizarVencidos()` |
| 173 líneas de CSS dentro del layout y 117 atributos `style` en las vistas | `public/css/erp-inpe.css` y componentes Blade (quedan 16 estilos en línea puntuales) |
| JavaScript repetido en cada vista (RUC, totales, confirmaciones) | `public/js/erp-inpe.js` |
| Rutas con funciones anónimas y llamadas externas | Controladores dedicados |
| Datos institucionales escritos en las vistas | `config/inpe.php`, configurable desde `.env` |

## 6. Recomendaciones pendientes

1. **Cambiar las claves de API** que estaban en el archivo `.env` original (Groq, OpenAI y apis.net.pe): se compartieron dentro del proyecto comprimido y deben considerarse expuestas.
2. Ejecutar `composer install` y probar cada módulo con datos reales antes de la sustentación.
3. Agregar pruebas automatizadas (`tests/Feature`) de los flujos principales: registrar, registrar pago y permisos por rol.
4. Si la entidad lo requiere, integrar los clasificadores de gasto y metas presupuestales del SIAF en la ejecución del gasto.

# Versión 4.0 — Rediseño institucional y CRUD en ventanas flotantes

## 1. Identidad visual del INPE
- Paleta tomada del logotipo: dorado, negro, verde y rojo. Se eliminó el azul.
- Escudo en el menú lateral, logotipo completo en el inicio de sesión y en las páginas de error, y escudo en el membrete de los PDF.
- Gráficos, tablas y estados con los mismos colores.

## 2. Pensado para personas mayores
- Letra base de 17 px, botones y campos más grandes, alto contraste (botón principal dorado con letra negra).
- Botones de acción con texto e icono: **Ver** (ojo), **Editar**, **Pagar**, **Eliminar**, **Conformidad**.
- Inicio con accesos rápidos grandes a las tareas más frecuentes.

## 3. Menú ordenado y CRUD completo en cada listado
- Un solo acceso por módulo: Inicio, Parque automotor, Suministro de alimentos, Operaciones recíprocas, Reportes, Asistente inteligente, Usuarios y permisos.
- Se retiraron del menú las opciones sueltas «Nuevo registro» y «Resumen mensual». Dentro de cada módulo hay pestañas (Registros / Resumen mensual, Vehículos / Mantenimientos, Usuarios / Roles, Reportes / Ejecución del gasto).
- Registrar, ver, editar, pagar, dar conformidad y eliminar se hace **desde el mismo listado**, en ventanas flotantes. Al guardar, el listado se actualiza sin perder los filtros.
- Los errores de validación se muestran dentro de la ventana, junto a cada campo.
- Las mismas pantallas siguen funcionando como página completa (enlaces directos, notificaciones).

## 4. Consulta de RUC en ambos sentidos
- Al escribir los 11 dígitos del RUC se completan la razón social y el domicilio.
- Al escribir el nombre del proveedor se muestra una lista de contribuyentes con su RUC.
- Fuentes: json.pe (`JSONPE_TOKEN`), decolecta / apis.net.pe (`APIPERU_TOKEN`) y los proveedores ya registrados en el sistema.

## 5. Terminología
- «Área contable» pasó a «Área Administrativa» en toda la aplicación, la documentación y los reportes.
