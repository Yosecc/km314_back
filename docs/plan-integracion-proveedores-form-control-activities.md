# Plan de integración de proveedores con FormControl y Activities

> Estado: implementado el 03/09/2026 y verificado con pruebas automatizadas.

## Objetivo

Incorporar el tipo de ingreso **Proveedor** al formulario de control y permitir registrar en Entradas/Salidas qué persona concreta de la empresa ingresa o sale.

Cuando distintos propietarios autoricen al mismo proveedor para una misma fecha, el ingreso físico debe registrarse una sola vez y quedar relacionado con todas las autorizaciones vigentes.

El diseño debe respetar estas reglas:

- El proveedor se guarda en el formulario de control.
- La actividad registra a la persona concreta que ingresa o sale.
- Los trabajadores del proveedor permanecen en `proveedores_empleados`.
- No se utiliza ni se modifica la tabla global `employees` para esta función.
- Los vehículos permanecen asociados al proveedor y no se duplican en sus trabajadores.
- Un proveedor puede tener varios formularios vigentes para distintos lotes, pero una entrada o salida representa un único movimiento físico.
- Deben funcionar tanto el QR permanente del proveedor como el QR individual de cualquiera de sus formularios.

## Verificación del flujo actual

Cada `FormControl` ya tiene un `quick_access_code` propio.

`ActivitiesResource` busca directamente un formulario mediante este código y asigna su `form_control_id`. Por lo tanto, el QR puede localizar un formulario de proveedor aunque no existan registros en `form_control_people`.

El problema aparece después de localizarlo: el flujo actual de Activities exige al menos una persona y solo procesa propietarios, empleados generales, familiares, visitantes espontáneos o personas de un formulario.

Además, `activities` solo admite un `form_control_id`. Esto no representa correctamente el caso en que seis propietarios crean seis formularios para el mismo proveedor: el guardia no debe elegir arbitrariamente uno ni registrar seis entradas para una sola visita.

## Plan propuesto

### 1. Relacionar el formulario con el proveedor

Agregar `proveedor_id`, nullable y con clave foránea, a `form_controls`.

Relaciones previstas:

- `FormControl belongsTo Proveedor`.
- `Proveedor hasMany FormControl`.

Esto permite identificar inequívocamente el proveedor sin depender únicamente del valor textual de `income_type`.

### 2. Agregar el tipo de ingreso Proveedor

Insertar mediante migración el valor activo `Proveedor` en `form_control_type_incomes`.

Como el selector actual obtiene sus opciones desde esa tabla, aparecerá en **Tipo de ingreso** cuando el tipo de acceso incluya `lote`.

### 3. Configurar el rango de acceso

Cuando `income_type` sea `Proveedor`:

- Permitir un único rango.
- Dejar editable únicamente la fecha de entrada.
- Al seleccionar la fecha de entrada:
  - establecer la hora de entrada en `07:00`, primera hora permitida;
  - establecer la fecha límite en el mismo día;
  - establecer la hora de salida en `18:00`, última hora permitida.
- Mantener deshabilitados los otros tres campos, pero enviarlos al servidor.
- Impedir agregar o eliminar rangos adicionales.

Las horas deben tomarse de la configuración utilizada actualmente por `FormControlResource`, evitando duplicar constantes.

### 4. Seleccionar el proveedor en el paso Personas

Para el tipo de ingreso `Proveedor`:

- Mostrar un selector obligatorio con proveedores activos.
- Ocultar y no deshidratar el repetidor normal de personas.
- No crear filas vacías en `form_control_people`.
- No copiar trabajadores del proveedor al formulario.
- No copiar vehículos del proveedor a los autos del formulario.

Los vehículos deben seguir siendo propiedad del proveedor y consultarse desde esa relación.

### 5. Buscar el formulario desde Activities

Activities no debe presentar cada formulario del proveedor como una entrada independiente. Debe agrupar todos los formularios que cumplan estas condiciones:

- pertenecer al mismo proveedor;
- estar autorizados;
- estar vigentes para el día y horario actuales.

La interfaz mostrará una sola opción de acceso con el proveedor, la cantidad de autorizaciones y la unión de los lotes habilitados. Los lotes repetidos deben aparecer una sola vez.

Ejemplo:

`Proveedor X — 6 autorizaciones — Lotes A12, B04, C08`

El guardia no debe seleccionar cuál de los seis formularios utilizar.

### 6. Búsqueda y funcionamiento de los QR

El proveedor tendrá un `quick_access_code` propio y permanente. Este será el QR principal para el funcionamiento operativo diario.

Al escanear el QR del proveedor:

1. Identificar el proveedor.
2. Buscar todas sus autorizaciones activas para ese momento.
3. Agrupar sus formularios y lotes.
4. Abrir el modal para identificar a la persona.

El QR individual de cada `FormControl` también debe continuar funcionando como alternativa. Al escanear cualquiera de esos QR:

1. Identificar el formulario y su proveedor.
2. No limitar el ingreso únicamente al formulario escaneado.
3. Buscar los demás formularios autorizados y vigentes del mismo proveedor.
4. Construir el mismo grupo de autorizaciones que se obtendría con el QR del proveedor.
5. Abrir el mismo modal de identificación de persona.

Por lo tanto, ambos caminos deben converger en un único proceso interno:

`QR proveedor o QR formulario -> proveedor -> formularios vigentes -> persona -> actividad`

Como búsqueda manual complementaria, los formularios de proveedor deberían poder localizarse por:

- ID o código rápido de cualquiera de sus formularios;
- código rápido del proveedor;
- nombre del proveedor;
- CUIT del proveedor;
- patente de un vehículo asociado al proveedor.

La búsqueda actual por DNI de `form_control_people` no sirve cuando el formulario del proveedor no contiene personas.

### 7. Cargar las personas al seleccionar el acceso

Cuando Activities seleccione —manualmente o mediante cualquiera de los dos QR— un acceso de proveedor, debe mostrar directamente un repetidor para cargar las personas que están ingresando o saliendo. No se utiliza un buscador ni un modal separado.

Datos previstos:

- DNI obligatorio, porque será la clave funcional para detectar si ya existe dentro del proveedor.
- Nombre.
- Apellido.
- Teléfono.
- Archivo del DNI opcional.

Al guardar la actividad:

1. Buscar el DNI únicamente entre los empleados del proveedor vinculado al formulario.
2. Si existe, utilizar ese registro sin duplicarlo.
3. Si no existe, crear una fila en `proveedores_empleados`.
4. Seleccionar esa persona para continuar con la actividad.

No se debe crear ni vincular ningún registro en `employees` o `employee_origens`.

### 8. Registrar una sola actividad para varios formularios

Agregar una tabla intermedia:

`activity_form_control`

Campos:

- `activity_id`.
- `form_control_id`.

Esta tabla permitirá que una sola actividad quede respaldada por todos los formularios vigentes del proveedor.

Para no afectar el flujo existente:

- `activities.form_control_id` se conserva para los tipos de ingreso actuales.
- Las actividades de proveedor utilizan la nueva relación múltiple.
- No se crean varias actividades para una misma entrada o salida.

Agregar también `proveedor_id` nullable a `activities`. Esto facilita encontrar la entrada abierta, registrar la salida y generar reportes sin tener que deducir siempre el proveedor recorriendo los formularios.

Reutilizar `activities_people` con:

- `model = ProveedorEmpleado`.
- `model_id = ID de proveedores_empleados`.

La actividad quedará asociada al proveedor y a todos los formularios que autorizaron el movimiento.

De esta manera se podrá determinar:

- qué formularios autorizaron el movimiento;
- a qué proveedor pertenece;
- qué persona concreta ingresó o salió;
- qué vehículo del proveedor utilizó.

### 9. Vehículos en Activities

Cuando el formulario seleccionado pertenezca a un proveedor, las opciones de vehículos deben obtenerse desde:

`actividad -> proveedor -> autos`

El vehículo seleccionado seguirá registrándose en `activities_autos`. No se deben duplicar autos bajo el modelo `FormControl` ni bajo el trabajador del proveedor.

### 10. Validaciones, salida y presentación

Ampliar Activities para reconocer `ProveedorEmpleado` en:

- validación de entrada cuando la persona ya se encuentra dentro;
- validación de salida cuando no existe una entrada abierta;
- obtención del nombre de la persona;
- vista y detalle de una actividad;
- filtros o búsquedas que dependan del tipo de persona.

Para registrar una salida, el sistema debe buscar la entrada abierta del proveedor y la persona durante el día actual. La salida debe quedar relacionada con el mismo conjunto de formularios que respaldó la entrada, sin pedir nuevamente al guardia que elija una autorización.

## Decisión central

La separación de responsabilidades queda así:

| Entidad | Responsabilidad |
| --- | --- |
| `form_controls` | Guarda la autorización y el proveedor mediante `proveedor_id`. |
| `proveedores_empleados` | Guarda las personas relacionadas con cada proveedor. |
| `proveedores` / `autos` | Mantienen los vehículos y sus documentos. |
| `proveedores.quick_access_code` | Proporciona el QR permanente del proveedor. |
| `activities` | Guarda un único movimiento y el proveedor correspondiente. |
| `activity_form_control` | Relaciona el movimiento con todos los formularios que lo autorizaron. |
| `activities_people` | Guarda la persona concreta que ingresó o salió. |
| `activities_autos` | Guarda el vehículo utilizado en el movimiento. |

## Decisiones aplicadas

- En cada fila del repetidor son obligatorios DNI, nombre y apellido; teléfono y archivo del DNI son opcionales.
- El DNI se busca dentro del proveedor seleccionado, por lo que el mismo DNI puede existir en proveedores diferentes.
- Cada movimiento identifica una persona del proveedor.
- La selección de vehículo permanece opcional.
- Una entrada se rechaza cuando no hay formularios autorizados y vigentes.
- Una salida recupera la entrada abierta de ese proveedor y persona durante el día y conserva exactamente sus formularios, aunque después hayan vencido.
- La actividad de proveedor guarda `proveedor_id`, la persona en `activities_people` y todas las autorizaciones en `activity_form_control`.

## Verificación realizada

- Migración aplicada primero sobre `km314_testing` y luego sobre `km314`.
- Suite: 12 pruebas aprobadas, 35 aserciones.
- Se verificaron ambos QR, la agrupación de formularios/lotes, la relación múltiple de actividades y la recuperación de la entrada al registrar una salida.
- Antes de migrar `km314` se creó y validó el respaldo `km314_backup_20260903_provider_plan`.
