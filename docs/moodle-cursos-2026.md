# Generación de CSV Moodle por curso (2026)

## Fecha

2026-04-13

## Objetivo

Generar archivos CSV separados para importación en Moodle, uno por curso, usando únicamente filas cuyo campo **Marca temporal** sea del año **2026**.

## Cursos y códigos

- Complementario Pintura -> `ComplPint26`
- Medios Audiovisuales -> `MedAud-26`
- Lenguaje Visual 1 -> `LVI-2026`

## Archivos involucrados

- Entrada: `data/raw/Estudiantes Inscripcion Plataforma.xlsx`
- Script: `scripts/generar_moodle_cursos_2026.sh`
- Salidas:
  - `data/processed/moodle/moodle_2026_ComplPint26.csv`
  - `data/processed/moodle/moodle_2026_MedAud-26.csv`
  - `data/processed/moodle/moodle_2026_LVI-2026.csv`

## Ejecución

Desde la raíz del repositorio:

```bash
./scripts/generar_moodle_cursos_2026.sh
```

## Resultado actual

- `ComplPint26`: 8 registros
- `MedAud-26`: 14 registros
- `LVI-2026`: 21 registros
- Filas 2026 omitidas por datos incompletos: 0

## Nota

Este proceso **solo genera archivos**. La importación en Moodle se realiza de forma manual.
