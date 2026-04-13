# Inscripción de usuarios existentes en cursos (2026)

## Fecha

2026-04-13

## Referencia oficial

Basado en la guía oficial de MoodleDocs disponible localmente en:

- `AUXILIAR/Importar alumnos - MoodleDocs.pdf`

Puntos relevantes de la guía:

- Los cursos se indican por su nombre corto (`course1`, `course2`, etc.).
- Si el usuario ya existe en Moodle, puede inscribirse en cursos sin alterar su información previa.

## Importante para este proyecto

Como los usuarios ya fueron cargados con `username = Dirección de correo electrónico`, los CSV de inscripción deben usar ese mismo `username` para que Moodle los encuentre.

## Script

- `scripts/generar_moodle_inscripciones_2026.sh`

Genera inscripciones solo para filas con `Marca temporal` 2026.

## Archivos generados

- `data/processed/moodle/moodle_inscripciones_2026_ComplPint26.csv` (8)
- `data/processed/moodle/moodle_inscripciones_2026_MedAud-26.csv` (14)
- `data/processed/moodle/moodle_inscripciones_2026_LVI-2026.csv` (21)

Formato de columnas:

- `username,course1,type1`

## Carga recomendada en Moodle

1. Ir a **Administración del sitio > Usuarios > Cuentas > Subir usuarios**.
2. Subir uno de los CSV de inscripción.
3. Configurar para trabajar con usuarios existentes (sin crear nuevos ni modificar datos).
4. Ejecutar la previsualización y confirmar la carga.
