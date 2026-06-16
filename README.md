# arteytecnologia.com.ar - Moodle local

Proyecto local para desarrollar mejoras sobre el reporte de participación en foros TP de Moodle.

## Objetivo inicial

Agregar una capa de calificación rápida al reporte existente, sin modificar tablas core de Moodle y sin tocar producción.

## Estructura

- `moodle/`: copia local de Moodle para pruebas. No se versiona.
- `moodledata/`: datos locales de Moodle. No se versiona.
- `db-dumps/`: exportaciones SQL. No se versiona.
- `docs/`: documentación del proyecto.
- `scripts/`: scripts auxiliares.
- `plugin/`: código propio del reporte/plugin a desarrollar.

## Seguridad

No subir a GitHub:

- `config.php` real.
- dumps SQL.
- `moodledata`.
- backups del servidor.
- contraseñas o claves.
