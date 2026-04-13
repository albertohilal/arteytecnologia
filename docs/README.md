# Documentación del proyecto

Este directorio centraliza la documentación operativa y técnica del repositorio.

## Índice

- [Carga de contactos Gmail desde inscripciones](contactos-gmail.md)
- [Generación de CSV Moodle por curso (2026)](moodle-cursos-2026.md)
- [Generación de CSV único de usuarios Moodle (2026)](moodle-usuarios-2026.md)
- [Inscripción de usuarios existentes en cursos (2026)](moodle-inscripciones-2026.md)

## Convención sugerida

- Un archivo por proceso o tema.
- Nombre descriptivo en minúsculas con guiones.
- Registrar fecha, objetivo, pasos, entradas/salidas y resultados.

## Convención de datos

- `data/raw/`: archivos fuente originales.
- `data/processed/gmail/`: CSV listos para importar en Google Contacts.
- `data/processed/moodle/`: CSV listos para importar en Moodle.
- Evitar mezclar archivos de salida entre plataformas.
