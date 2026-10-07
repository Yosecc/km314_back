# Plan del módulo Recepción de paquetes

> Estado: implementado el 05/09/2026. Migrado en `km314` luego de validar la suite completa en `km314_testing`.
>
> Respaldo previo: `km314_backup_20260905_package_receptions`.
>
> Nombre recomendado en el menú: **Recepción de paquetes**. Es más claro que “Recepción de correo”, porque el módulo controla paquetes físicos y no mensajes de correo electrónico.

## 1. Problema que resuelve

Los propietarios necesitan avisar con anticipación que recibirán un paquete. Actualmente, cuando llega el correo o transportista, recepción debe llamar al propietario para pedir datos, validar el envío y decidir si puede recibirlo.

El módulo permitirá que el propietario registre previamente el envío. Recepción podrá consultar la información, recibir el paquete, custodiarlo y registrar su entrega final con una trazabilidad completa.

## 2. Objetivos

- Evitar llamadas innecesarias al propietario cuando llega un paquete esperado.
- Mostrar a recepción qué paquetes se esperan hoy y dentro de qué franja horaria.
- Registrar quién realizó cada acción y en qué momento.
- Notificar al propietario ante cambios importantes.
- Detectar paquetes que no llegaron y paquetes recibidos que llevan demasiado tiempo sin retirarse.
- Mantener fotografías, documentos y datos sensibles con acceso controlado.

## 3. Roles involucrados

### Propietario

- Crear una recepción para sí mismo.
- Ver únicamente sus recepciones.
- Editar o cancelar una recepción mientras todavía está en espera.
- Consultar cuándo fue recibido y cuándo fue entregado.
- Recibir notificaciones por cambios de estado.

El `owner_id` se obtiene automáticamente del usuario autenticado. Si tiene varios lotes, debe seleccionar el lote de destino; si tiene uno solo, se completa automáticamente.

### Recepción / Seguridad

- Ver las recepciones de todos los propietarios.
- Buscar por propietario, lote, empresa de correo, código, referencia o franja horaria.
- Marcar un paquete como recibido.
- Registrar la entrega al propietario o a un tercero autorizado.
- Cancelar con motivo obligatorio cuando corresponda.
- Ver alertas operativas.

### Administrador / Superadministrador

- Todas las funciones operativas.
- Corregir datos con trazabilidad.
- Ver historial completo y registros cancelados.
- Configurar los tiempos de alerta y recordatorio.

## 4. Datos de la recepción

### Datos internos obligatorios

- Propietario (`owner_id`), inferido del usuario o seleccionado por administración.
- Lote (`lote_id`), inferido cuando sea posible.
- Usuario que creó el registro (`created_by_user_id`).
- Estado actual.
- Folio interno único generado automáticamente, por ejemplo `PAQ-8F3K2Q`.

### Datos que completa el propietario

Obligatorios:

- Nombre del correo o transportista: Mercado Libre, OCA, Andreani, Correo Argentino, etc.
- Fecha y hora estimada desde.
- Fecha y hora estimada hasta.

Opcionales:

- Código o palabra clave solicitada por el transportista.
- Número de seguimiento, compra o referencia.
- Nombre de la persona que realizó la compra o recibirá el paquete.
- DNI y teléfono de esa persona.
- Cantidad estimada de bultos.
- Varias imágenes o comprobantes.
- Observaciones.

Reglas recomendadas:

- La fecha final debe ser posterior a la inicial.
- Permitir franjas que crucen de un día al siguiente.
- El código o palabra clave se considera información sensible: debe ocultarse visualmente por defecto y revelarse mediante una acción explícita.
- No hacer obligatorios los datos opcionales aunque estén vacíos.

## 5. Estados y ciclo de vida

Estados persistentes recomendados:

1. `Esperado`: fue registrado y todavía no llegó.
2. `Recibido`: recepción tiene físicamente el paquete y espera que sea retirado.
3. `Entregado`: el paquete fue entregado al propietario o a un tercero.
4. `Cancelado`: el registro fue cancelado y conserva el motivo.

Transiciones válidas:

```text
Esperado ──> Recibido ──> Entregado
    └────────> Cancelado
```

No se debe cambiar automáticamente el estado cuando vence el rango horario. En su lugar se calculan indicadores operativos:

- **No llegó en horario**: sigue `Esperado` y la hora final ya pasó.
- **Llega próximamente**: faltan menos de 30 minutos para el inicio del rango.
- **Retiro demorado**: sigue `Recibido` después del tiempo configurado.
- **Retiro crítico**: continúa en recepción después de un segundo umbral mayor.

Esta separación permite recibir un paquete tarde sin falsear o perder su historial.

## 6. Acciones operativas

### Registrar recepción anticipada

- La realiza normalmente el propietario.
- Estado inicial: `Esperado`.
- Guarda el evento de creación.
- Aparece inmediatamente en el monitor de recepción.

### Marcar como recibido

Disponible solo desde `Esperado`, incluso si el rango ya venció.

Datos:

- Foto opcional del paquete.
- Cantidad real de bultos opcional.
- Observación opcional: estado del paquete, embalaje dañado, ubicación interna, etc.

Automáticamente registra:

- Usuario que recibió.
- Fecha y hora exactas.
- Cambio de estado.
- Notificación al propietario.

### Cancelar

Disponible desde `Esperado`.

- Motivo o nota obligatoria.
- Usuario que canceló.
- Fecha y hora exactas.
- Notificación al propietario.

Una recepción cancelada no se elimina. Queda visible en el historial.

### Entregar el paquete

Disponible solo desde `Recibido`.

Se debe indicar quién retira:

- **Propietario**: no requiere foto de DNI.
- **Tercero**: nombre y DNI obligatorios; foto del DNI obligatoria.

Datos adicionales:

- Foto de la entrega opcional.
- Observación opcional.
- Cantidad de bultos entregados, si se registró más de uno.

Automáticamente registra:

- Usuario de recepción que realizó la entrega.
- Fecha y hora exactas.
- Identidad de quien retiró.
- Cambio de estado.
- Notificación al propietario.

### Correcciones administrativas

No se recomienda editar directamente el estado ni sobrescribir actores/fechas. Una corrección debe crear un evento de auditoría con usuario, fecha, valor anterior, valor nuevo y motivo obligatorio.

## 7. Notificaciones

Canal inicial recomendado: notificaciones de base de datos de Filament, que ya existe en el proyecto.

Notificaciones al propietario:

- Paquete recibido.
- Recepción cancelada, incluyendo el motivo.
- Paquete entregado, indicando quién lo retiró.
- El paquete no llegó dentro de la franja prevista.
- Recordatorio de paquete pendiente de retiro.

Notificaciones internas opcionales:

- Nueva recepción creada para hoy.
- Recepción creada cuando la franja ya comenzó.
- Paquete esperado sin llegar.
- Paquete olvidado en recepción.

Para no duplicar avisos automáticos deben registrarse marcas como `overdue_notified_at`, `pickup_reminder_sent_at` y `pickup_critical_sent_at`, o una tabla de entregas de notificaciones con clave única por recepción y tipo.

Valores iniciales sugeridos, configurables:

- Aviso “no llegó”: una vez, al superar el fin del rango.
- Retiro demorado: 24 horas después de recibirlo.
- Retiro crítico: 72 horas después de recibirlo.
- No enviar repetidamente el mismo aviso.

Un comando programado cada cinco minutos puede detectar estas condiciones. Debe usar `withoutOverlapping()` para evitar ejecuciones simultáneas.

## 8. Modelo de datos recomendado

### Tabla `package_receptions`

- `id`.
- `reference_code`, único.
- `owner_id`, clave foránea.
- `lote_id`, clave foránea.
- `created_by_user_id`, clave foránea.
- `courier_name`.
- `expected_from`.
- `expected_until`.
- `carrier_access_code`, nullable y cifrado mediante cast del modelo.
- `tracking_number`, nullable.
- `recipient_name`, nullable.
- `recipient_dni`, nullable.
- `recipient_phone`, nullable.
- `expected_packages_count`, nullable.
- `received_packages_count`, nullable.
- `observations`, text nullable.
- `status`: `expected`, `received`, `delivered` o `cancelled`.
- `received_at`, nullable.
- `received_by_user_id`, nullable.
- `received_notes`, nullable.
- `cancelled_at`, nullable.
- `cancelled_by_user_id`, nullable.
- `cancellation_reason`, nullable.
- `delivered_at`, nullable.
- `delivered_by_user_id`, nullable; es el usuario de recepción que entrega.
- `delivered_to_type`, nullable: `owner` o `third_party`.
- `delivered_to_name`, nullable.
- `delivered_to_dni`, nullable.
- `delivery_notes`, nullable.
- Marcas de notificaciones automáticas.
- `created_at`, `updated_at` y `deleted_at`.

Índices recomendados:

- `owner_id, status`.
- `lote_id, status`.
- `status, expected_until`.
- `status, received_at`.
- `courier_name`.
- Índice único para `reference_code`.

### Tabla `package_reception_files`

- `id`.
- `package_reception_id`.
- `category`: `registration`, `received`, `delivery`, `delivery_identity` o `cancellation`.
- `path`.
- `original_name`.
- `mime_type`, nullable.
- `size`, nullable.
- `uploaded_by_user_id`.
- `created_at`, `updated_at`.

Una tabla única de archivos permite almacenar varias imágenes por etapa sin agregar columnas rígidas al registro principal.

### Tabla `package_reception_events`

- `id`.
- `package_reception_id`.
- `event_type`: creación, edición, recepción, cancelación, entrega, alerta o corrección.
- `from_status`, nullable.
- `to_status`, nullable.
- `actor_user_id`, nullable para eventos automáticos.
- `notes`, nullable.
- `metadata`, JSON nullable.
- `occurred_at`.
- `created_at`, `updated_at`.

Esta tabla es el historial inmutable y permite responder quién hizo cada acción, cuándo y con qué información.

## 9. Relaciones

- `PackageReception belongsTo Owner`.
- `PackageReception belongsTo Lote`.
- `PackageReception belongsTo User` mediante creador, receptor, cancelador y entregador.
- `PackageReception hasMany PackageReceptionFile`.
- `PackageReception hasMany PackageReceptionEvent`.
- `Owner hasMany PackageReception`.
- `Lote hasMany PackageReception`.

## 10. Servicio de dominio

Crear un servicio `PackageReceptionService` para concentrar las transiciones:

- `createExpected()`.
- `receive()`.
- `cancel()`.
- `deliver()`.
- `correct()`.

Cada transición debe ejecutarse dentro de una transacción y utilizar `lockForUpdate()` sobre la recepción. Esto evita que dos usuarios marquen simultáneamente el mismo paquete como recibido o entregado.

El servicio debe encargarse de:

- Validar la transición de estado.
- Guardar actor y fecha.
- Guardar archivos.
- Crear el evento de auditoría.
- Enviar la notificación después de confirmar la transacción.

Los cambios de estado no deben distribuirse entre callbacks distintos del Resource.

## 11. Interfaz del propietario

Un recurso sencillo y enfocado:

- Botón principal **Avisar nuevo paquete**.
- Formulario corto con transportista y rango primero.
- Campos opcionales agrupados bajo “Información adicional”.
- Carga múltiple de imágenes con vista previa.
- Lista de sus paquetes con estado, franja, lote y última actualización.
- Acciones de editar o cancelar solamente mientras esté `Esperado`.
- Detalle con línea de tiempo y archivos.

Si el usuario propietario tiene un único lote, este no debe preguntarse. Si tiene varios, se muestran radios con sus lotes.

## 12. Monitor de recepción

Crear una página Livewire/Filament visualmente alineada con `MonitorAccesos`, pero diseñada para el ciclo de paquetes.

### Encabezado

- Título **Recepción de paquetes**.
- Indicador de actualización en vivo.
- Hora de última actualización.
- Botón pausar/reanudar actualización.
- Botón **Registrar paquete** para recepción cuando el propietario no pudo hacerlo.

### Indicadores superiores

- Esperados hoy.
- No llegaron en horario.
- En recepción pendientes de retiro.
- Entregados hoy.
- Alertas activas.

### Filtros

- Buscador único por folio, propietario, DNI, lote, transportista o seguimiento.
- Período: hoy, próximas 24 horas, 7 días, histórico.
- Estado: todos, esperados, recibidos, entregados, cancelados.
- Filtro rápido: atrasados o retiro demorado.

### Distribución propuesta

```text
┌────────────────────────────────────────────────────────────────────┐
│ RECEPCIÓN DE PAQUETES · EN VIVO         Actualizado 14:32  [Pausa]│
├────────────┬────────────┬────────────┬────────────┬────────────────┤
│ Esperados  │ Atrasados  │ En guarda  │ Entregados │ Alertas        │
│     12     │      3     │      8     │      17    │      4         │
├────────────────────────────────────────────┬───────────────────────┤
│ Buscar...   Hoy | 24 h | 7 días           │                       │
│                                            │ ALERTAS               │
│ 09:00–12:00  Mercado Libre        ESPERADO │ • No llegó: Lote A1  │
│ Juan Pérez · Lote A1 · PAQ-8F3K2Q          │ • 3 días en guarda    │
│ [Ver código] [Recibir] [Cancelar]          │                       │
│                                            ├───────────────────────┤
│ 11:18  OCA                       RECIBIDO   │ EN RECEPCIÓN          │
│ Ana Gómez · Lote B4 · hace 2 h             │ 8 paquetes pendientes │
│ [Ver detalle] [Entregar]                   │ de retiro             │
└────────────────────────────────────────────┴───────────────────────┘
```

### Tarjeta de paquete

Debe mostrar de un vistazo:

- Franja esperada o tiempo transcurrido desde la recepción.
- Transportista.
- Estado con color.
- Propietario y lote.
- Folio interno.
- Cantidad de bultos.
- Miniaturas si existen.
- Código oculto con botón **Revelar** y **Copiar**.
- Acciones válidas según el estado.

Colores sugeridos:

- Azul: esperado.
- Rojo: no llegó en horario.
- Ámbar: recibido y pendiente de retiro.
- Verde: entregado.
- Gris: cancelado.
- Naranja intenso: retiro demorado.

### Detalle lateral o modal amplio

- Toda la información del envío.
- Galería de imágenes por etapa.
- Línea de tiempo de eventos.
- Actores y horarios.
- Acciones operativas sin abandonar el monitor.

Actualización sugerida: polling cada 15 o 30 segundos, con opción de pausa, siguiendo el patrón del Monitor de accesos.

## 13. Seguridad y privacidad

- Los propietarios solo pueden consultar y modificar registros cuyo `owner_id` les pertenece.
- Recepción y seguridad no deben poder editar datos históricos luego de una entrega.
- Las fotos de DNI y códigos de entrega no deben exponerse mediante URLs públicas permanentes.
- Guardar archivos en disco privado y servirlos con rutas autorizadas o URLs temporales.
- Validar tipo MIME, extensión y tamaño; aceptar inicialmente JPG, PNG, WEBP y PDF.
- Registrar quién reveló o copió un código sensible si se necesita auditoría reforzada.
- No borrar físicamente recepciones desde la interfaz normal; usar cancelación y soft delete administrativo.

## 14. Permisos propuestos

- `view_any_package_reception`.
- `view_package_reception`.
- `create_package_reception`.
- `update_package_reception`.
- `cancel_package_reception`.
- `receive_package_reception`.
- `deliver_package_reception`.
- `correct_package_reception`.
- `view_package_reception_sensitive_data`.
- `view_package_reception_monitor`.

Asignación inicial recomendada:

| Capacidad | Propietario | Seguridad/Recepción | Administrador |
| --- | ---: | ---: | ---: |
| Crear y ver propios | Sí | Sí, todos | Sí, todos |
| Editar esperado propio | Sí | Sí | Sí |
| Recibir | No | Sí | Sí |
| Entregar | No | Sí | Sí |
| Cancelar | Sí, propio esperado | Sí | Sí |
| Corregir historial | No | No | Sí |
| Ver códigos sensibles | Propios | Sí | Sí |

## 15. Consultas y rendimiento

Crear un servicio de consulta específico para el monitor, evitando lógica compleja dentro de Blade:

- Totales del día.
- Esperados vigentes.
- Esperados atrasados.
- Recibidos pendientes.
- Retiro demorado.
- Historial filtrado.

Usar eager loading de propietario, lote, actores y archivos para evitar consultas N+1. Limitar el historial visible y paginar resultados antiguos.

## 16. Pruebas necesarias antes de habilitarlo

### Modelo y validación

- Generación del folio único.
- Rango final posterior al inicial.
- Campos opcionales realmente opcionales.
- Cifrado y lectura autorizada del código del transportista.
- Asociación correcta con propietario y lote.

### Permisos

- Un propietario no puede ver recepciones de otro.
- Seguridad puede operar, pero no corregir historial.
- Solo administración puede realizar correcciones.
- Acceso protegido a imágenes de DNI.

### Estados

- Solo `Esperado` puede pasar a `Recibido` o `Cancelado`.
- Solo `Recibido` puede pasar a `Entregado`.
- No permite recibir, cancelar o entregar dos veces.
- La cancelación exige motivo.
- La entrega a un tercero exige nombre, DNI y foto del DNI.
- La entrega al propietario no exige foto del DNI.

### Auditoría y notificaciones

- Cada transición registra actor y hora.
- Cada transición crea un evento.
- El propietario recibe una notificación por cambio de estado.
- Las alertas automáticas se envían una sola vez.
- Una notificación no debe enviarse si la transacción falla.

### Monitor

- Cálculo correcto de “no llegó en horario”.
- Cálculo correcto de retiro demorado y crítico.
- Estadísticas y filtros.
- Búsqueda por propietario, lote, transportista, folio y seguimiento.
- Acciones visibles únicamente cuando corresponden al estado.

### Archivos

- Varias imágenes en el registro inicial.
- Foto opcional al recibir.
- Foto obligatoria de DNI para tercero.
- Rechazo de archivos inválidos o demasiado grandes.

## 17. Orden de implementación menos invasivo

1. Crear pruebas base y protección estricta de `km314_testing`.
2. Crear migraciones, modelos, relaciones y enums/constantes de estado.
3. Implementar `PackageReceptionService` con transacciones y eventos.
4. Implementar policies y permisos antes de exponer rutas.
5. Crear el formulario y listado del propietario.
6. Crear las acciones Recibir, Cancelar y Entregar.
7. Agregar notificaciones de cambios de estado.
8. Crear el monitor de recepción y sus consultas optimizadas.
9. Agregar comando programado para alertas temporales y deduplicación.
10. Probar archivos privados y reglas para entrega a terceros.
11. Ejecutar migración primero en pruebas, validar respaldo y luego migrar `km314`.

## 18. Alcance recomendado para una primera versión

Incluir:

- Registro por propietario o recepción.
- Estados Esperado, Recibido, Entregado y Cancelado.
- Archivos por etapa.
- Auditoría completa.
- Notificaciones internas al propietario.
- Alertas de no llegada y retiro demorado.
- Monitor operativo.

Dejar para una segunda etapa:

- Notificaciones por correo electrónico, WhatsApp o push móvil.
- Lectura automática de etiquetas o códigos de barras.
- Firma manuscrita de quien retira.
- Integración con APIs de transportistas.
- Inventario físico por estantería o ubicación dentro de recepción.

## 19. Decisiones propuestas para aprobar

Estas decisiones permiten implementar sin ambigüedades:

1. Nombre visible: **Recepción de paquetes**.
2. Estados: Esperado, Recibido, Entregado y Cancelado.
3. “No llegó” y “olvidado” son alertas calculadas, no estados.
4. Propietario y lote son relaciones internas obligatorias, aunque se autocompleten.
5. El motivo de cancelación es obligatorio.
6. Si retira un tercero, nombre, DNI y foto del DNI son obligatorios.
7. Foto al recibir y foto general de entrega son opcionales.
8. Aviso de retiro demorado a las 24 horas y crítico a las 72 horas, configurables.
9. Los códigos sensibles y las fotos de DNI se almacenan de forma privada.
10. La primera versión utiliza notificaciones internas de Filament.
