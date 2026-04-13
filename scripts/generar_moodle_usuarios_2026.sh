#!/usr/bin/env bash
set -euo pipefail

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
INPUT_XLSX="${1:-$BASE_DIR/data/raw/Estudiantes Inscripcion Plataforma.xlsx}"
TMP_DIR="$BASE_DIR/data/.tmp_contactos"
TMP_CSV="$TMP_DIR/Estudiantes Inscripcion Plataforma.csv"
OUTPUT_CSV="${2:-$BASE_DIR/data/processed/moodle/moodle_usuarios_2026.csv}"
REJECTS_CSV="${3:-$BASE_DIR/data/processed/moodle/moodle_usuarios_2026_rechazados.csv}"

if ! command -v libreoffice >/dev/null 2>&1; then
  echo "Error: libreoffice no está instalado." >&2
  exit 1
fi

if [[ ! -f "$INPUT_XLSX" ]]; then
  echo "Error: no existe el archivo de entrada: $INPUT_XLSX" >&2
  exit 1
fi

mkdir -p "$TMP_DIR" "$(dirname "$OUTPUT_CSV")" "$(dirname "$REJECTS_CSV")"

libreoffice --headless --convert-to csv --outdir "$TMP_DIR" "$INPUT_XLSX" >/dev/null 2>&1

python3 - "$TMP_CSV" "$OUTPUT_CSV" "$REJECTS_CSV" <<'PY'
import csv
import re
import sys
from datetime import datetime
from pathlib import Path

src = Path(sys.argv[1])
out = Path(sys.argv[2])
rejects = Path(sys.argv[3])

with src.open("r", encoding="latin-1", newline="") as f:
    rows = list(csv.reader(f))

if not rows:
    raise SystemExit("CSV fuente vacío")

HEADER = ["username", "password", "firstname", "lastname", "email"]

IDX = {
    "ts": 0,
    "email_username": 1,
    "firstname": 4,
    "lastname": 5,
    "dni": 7,
}

def parse_ts(value: str):
    value = value.strip()
    try:
        return datetime.strptime(value, "%d/%m/%Y %H:%M:%S")
    except ValueError:
        return None

def clean_email(value: str) -> str:
    return value.strip().lower()

def clean_dni(value: str) -> str:
    value = value.strip()
    digits = re.sub(r"\D", "", value)
    return digits

email_re = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")

# Dedupe por username (email). Si se repite, se conserva el registro más reciente.
by_user = {}
invalid_rows = 0
rejected_rows = []
duplicate_rows = 0

def reject(reason: str, row: list):
    global invalid_rows
    invalid_rows += 1
    ts = row[IDX["ts"]].strip() if len(row) > IDX["ts"] else ""
    email_username = row[IDX["email_username"]].strip() if len(row) > IDX["email_username"] else ""
    firstname = row[IDX["firstname"]].strip() if len(row) > IDX["firstname"] else ""
    lastname = row[IDX["lastname"]].strip() if len(row) > IDX["lastname"] else ""
    dni_raw = row[IDX["dni"]].strip() if len(row) > IDX["dni"] else ""
    rejected_rows.append([reason, ts, email_username, firstname, lastname, dni_raw])

for row in rows[1:]:
    ts_raw = row[IDX["ts"]] if len(row) > IDX["ts"] else ""
    if "/2026 " not in ts_raw:
        continue

    username = clean_email(row[IDX["email_username"]] if len(row) > IDX["email_username"] else "")
    password = clean_dni(row[IDX["dni"]] if len(row) > IDX["dni"] else "")
    firstname = (row[IDX["firstname"]] if len(row) > IDX["firstname"] else "").strip()
    lastname = (row[IDX["lastname"]] if len(row) > IDX["lastname"] else "").strip()

    if not username:
        reject("username vacío", row)
        continue

    if not firstname or not lastname:
        reject("nombre/apellido incompleto", row)
        continue

    if not password:
        reject("DNI vacío o no numérico", row)
        continue

    if len(password) < 7:
        reject("DNI demasiado corto (<7)", row)
        continue

    if not email_re.match(username):
        reject("email inválido", row)
        continue

    ts = parse_ts(ts_raw)
    current = [username, password, firstname, lastname, username]

    if username not in by_user:
        by_user[username] = (ts, current)
    else:
        duplicate_rows += 1
        prev_ts, _ = by_user[username]
        if ts and (prev_ts is None or ts > prev_ts):
            by_user[username] = (ts, current)

records = [item[1] for item in by_user.values()]
records.sort(key=lambda r: r[0])

with out.open("w", encoding="utf-8", newline="") as f:
    w = csv.writer(f)
    w.writerow(HEADER)
    w.writerows(records)

with rejects.open("w", encoding="utf-8", newline="") as f:
    w = csv.writer(f)
    w.writerow(["motivo", "marca_temporal", "direccion_correo_electronico", "nombres", "apellidos", "dni_original"])
    w.writerows(rejected_rows)

print(f"OK: generado {out}")
print(f"OK: reporte de rechazados {rejects}")
print(f"Usuarios exportados (únicos, 2026): {len(records)}")
print(f"Filas 2026 omitidas por datos inválidos/incompletos: {invalid_rows}")
print(f"Filas 2026 duplicadas por username/email: {duplicate_rows}")
PY
