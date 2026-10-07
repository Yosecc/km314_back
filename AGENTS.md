# Instrucciones del proyecto

Este archivo regula únicamente permisos y diseño de monitores. No establece otras reglas sobre cómo programar el proyecto.

## Permisos y acceso

- Filament Shield es la única fuente de verdad para controlar permisos, acceso a recursos, páginas, widgets y su visibilidad en el menú.
- Toda entidad nueva de Filament debe tener sus permisos generados en Shield y respetar el permiso asignado al usuario o a su rol.
- No se debe conceder acceso mediante excepciones directas como `hasRole('owner')`, uniendo esa condición con `OR` a un permiso de Shield.
- Los roles pueden utilizarse para limitar datos o comportamiento una vez autorizado el acceso, pero no para saltarse un permiso desmarcado en Shield.
- Las páginas personalizadas deben integrarse con Shield, por ejemplo mediante `HasPageShield`, para que tanto el menú como el acceso directo por URL respeten el permiso de página.

## Diseño de monitores

- Todos los monitores deben conservar el lenguaje visual de los monitores existentes del proyecto: encabezado destacado, contadores de resumen, filtros visibles, buscador, tarjetas operativas y estados fáciles de identificar.
- Cada monitor puede utilizar un color principal diferente, manteniendo la misma estructura, jerarquía visual y experiencia de uso.
- El diseño debe funcionar correctamente en modo claro, modo oscuro y pantallas móviles.
- Las acciones principales deben estar disponibles directamente en cada tarjeta mediante botones claros y visibles.
- No reemplazar este patrón por una tabla estándar de Filament salvo que el usuario lo solicite expresamente.
