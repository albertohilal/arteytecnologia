# Generación de CSV único de usuarios Moodle (2026)

## Fecha

2026-04-13

## Objetivo

Generar un único CSV para alta de usuarios en Moodle, sin inscripción a cursos.

## Criterios

- Solo filas con `Marca temporal` del año 2026.
- `username` = `Dirección de correo electrónico`.
- `password` = `DNI` (solo dígitos).
- Sin columnas `course1/course2/type1` en este flujo.
- Validaciones previas:
	- email con formato válido,
	- DNI numérico y longitud mínima de 7,
	- nombre y apellido obligatorios.

## Archivos

- Entrada: `data/raw/Estudiantes Inscripcion Plataforma.xlsx`
- Script: `scripts/generar_moodle_usuarios_2026.sh`
- Salida: `data/processed/moodle/moodle_usuarios_2026.csv`
- Rechazados: `data/processed/moodle/moodle_usuarios_2026_rechazados.csv`

## Ejecución

```bash
./scripts/generar_moodle_usuarios_2026.sh
```

## Resultado actual

- Usuarios exportados (únicos, 2026): 33
- Filas 2026 omitidas por datos inválidos/incompletos: 0
- Filas 2026 duplicadas por username/email: 0

## Nota

Este proceso solo genera el archivo CSV. La carga en Moodle se hace manualmente.
