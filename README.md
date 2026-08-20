# arteytecnologia.com.ar - Moodle local

Proyecto local para desarrollar mejoras sobre el reporte de participación en foros TP de Moodle.

## Objetivo inicial

Agregar una capa de calificación rápida al reporte existente, sin modificar tablas core de Moodle y sin tocar producción.

## Entornos

- Proyecto: `~/Proyectos/arteytecnologia.com.ar`
- Moodle local: http://localhost:8080
- phpMyAdmin: http://localhost:8081
- Producción: https://arteytecnologia.com.ar/ (no es entorno de pruebas)

## Estructura

- `moodle/`: copia local de Moodle para pruebas. El core no se versiona, salvo excepciones de código propio (p. ej. `moodle/reportes/reporteTPporCurso.php`).
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
