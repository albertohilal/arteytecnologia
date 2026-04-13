#!/usr/bin/env bash
set -euo pipefail

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
INPUT_XLSX="${1:-$BASE_DIR/data/raw/Estudiantes Inscripcion Plataforma.xlsx}"
TMP_DIR="$BASE_DIR/data/.tmp_contactos"
TMP_CSV="$TMP_DIR/Estudiantes Inscripcion Plataforma.csv"
OUT_DIR="${2:-$BASE_DIR/data/processed/moodle}"

if ! command -v libreoffice >/dev/null 2>&1; then
  echo "Error: libreoffice no está instalado." >&2
  exit 1
fi

if [[ ! -f "$INPUT_XLSX" ]]; then
  echo "Error: no existe el archivo de entrada: $INPUT_XLSX" >&2
  exit 1
fi

mkdir -p "$TMP_DIR" "$OUT_DIR"

libreoffice --headless --convert-to csv --outdir "$TMP_DIR" "$INPUT_XLSX" >/dev/null 2>&1

python3 - "$TMP_CSV" "$OUT_DIR" <<'PY'
import csv
import re
import sys
import unicodedata
from pathlib import Path

src = Path(sys.argv[1])
out_dir = Path(sys.argv[2])

HEADER = [
    "username",
    "password",
    "firstname",
    "lastname",
    "email",
    "idnumber",
    "country",
    "city",
    "course1",
    "course2",
    "type1",
]

COURSES = {
    "ComplPint26": out_dir / "moodle_2026_ComplPint26.csv",
    "MedAud-26": out_dir / "moodle_2026_MedAud-26.csv",
    "LVI-2026": out_dir / "moodle_2026_LVI-2026.csv",
}

def normalize_text(value: str) -> str:
    value = value.strip()
    value = unicodedata.normalize("NFKD", value)
    value = "".join(ch for ch in value if not unicodedata.combining(ch))
    return value.lower()

def is_yes(value: str) -> bool:
    text = normalize_text(value)
    if not text:
        return False
    if "si" in text:
        return True
    return False

def clean_username(value: str) -> str:
    value = value.strip().lower()
    value = value.replace(" ", "")
    value = re.sub(r"[^a-z0-9._-]", "", value)
    return value

def clean_email(value: str) -> str:
    return value.strip().lower()

with src.open("r", encoding="latin-1", newline="") as f:
    reader = csv.reader(f)
    rows = list(reader)

if not rows:
    raise SystemExit("CSV temporal vacío")

idx = {name: i for i, name in enumerate(rows[0])}

required = {
    "Marca temporal": 0,
    "Nombres": 4,
    "Apellidos": 5,
    "Email": 6,
    "Dirección de correo electrónico": 1,
    "Tu nombre de usuario (AL MENOS 8 CARACTERES sin MAYUSCULAS ni ESPACIOS)": 2,
    "Tu password (AL MENOS 8 CARACTERES)": 3,
    "Complementario Pintura": 8,
    "Medios Audiovisuales...": 9,
    "Lenguaje Visual 1": 11,
}

files = {k: v.open("w", encoding="utf-8", newline="") for k, v in COURSES.items()}
writers = {k: csv.writer(f) for k, f in files.items()}
for w in writers.values():
    w.writerow(HEADER)

counts = {k: 0 for k in COURSES}
skipped = 0

for row in rows[1:]:
    ts = row[required["Marca temporal"]].strip() if len(row) > required["Marca temporal"] else ""
    if "/2026 " not in ts:
        continue

    username = clean_username(row[required["Tu nombre de usuario (AL MENOS 8 CARACTERES sin MAYUSCULAS ni ESPACIOS)"]] if len(row) > required["Tu nombre de usuario (AL MENOS 8 CARACTERES sin MAYUSCULAS ni ESPACIOS)"] else "")
    password = (row[required["Tu password (AL MENOS 8 CARACTERES)"]] if len(row) > required["Tu password (AL MENOS 8 CARACTERES)"] else "").strip()
    firstname = (row[required["Nombres"]] if len(row) > required["Nombres"] else "").strip()
    lastname = (row[required["Apellidos"]] if len(row) > required["Apellidos"] else "").strip()
    email = clean_email(row[required["Email"]] if len(row) > required["Email"] else "")
    email2 = clean_email(row[required["Dirección de correo electrónico"]] if len(row) > required["Dirección de correo electrónico"] else "")

    if not email:
        email = email2

    if not (username and password and firstname and lastname and email):
        skipped += 1
        continue

    base = [
        username,
        password,
        firstname,
        lastname,
        email,
        "",
        "ar",
        "Quilmes",
        "",
        "",
        "1",
    ]

    cp = row[required["Complementario Pintura"]] if len(row) > required["Complementario Pintura"] else ""
    ma = row[required["Medios Audiovisuales..."]] if len(row) > required["Medios Audiovisuales..."] else ""
    lv = row[required["Lenguaje Visual 1"]] if len(row) > required["Lenguaje Visual 1"] else ""

    if is_yes(cp):
        rec = base.copy()
        rec[8] = "ComplPint26"
        writers["ComplPint26"].writerow(rec)
        counts["ComplPint26"] += 1

    if is_yes(ma):
        rec = base.copy()
        rec[8] = "MedAud-26"
        writers["MedAud-26"].writerow(rec)
        counts["MedAud-26"] += 1

    if is_yes(lv):
        rec = base.copy()
        rec[8] = "LVI-2026"
        writers["LVI-2026"].writerow(rec)
        counts["LVI-2026"] += 1

for f in files.values():
    f.close()

for course, path in COURSES.items():
    print(f"OK: {course}: {counts[course]} -> {path}")
print(f"Filas 2026 omitidas por datos incompletos: {skipped}")
PY
