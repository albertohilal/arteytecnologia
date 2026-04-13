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
import sys
import unicodedata
from pathlib import Path

src = Path(sys.argv[1])
out_dir = Path(sys.argv[2])

courses = {
    "ComplPint26": out_dir / "moodle_inscripciones_2026_ComplPint26.csv",
    "MedAud-26": out_dir / "moodle_inscripciones_2026_MedAud-26.csv",
    "LVI-2026": out_dir / "moodle_inscripciones_2026_LVI-2026.csv",
}

with src.open("r", encoding="latin-1", newline="") as f:
    rows = list(csv.reader(f))

def norm(value: str) -> str:
    value = value.strip().lower()
    value = unicodedata.normalize("NFKD", value)
    value = "".join(ch for ch in value if not unicodedata.combining(ch))
    return value

def is_yes(value: str) -> bool:
    return "si" in norm(value)

idx = {
    "ts": 0,
    "email_username": 1,
    "cp": 8,
    "ma": 9,
    "lv": 11,
}

data = {k: [] for k in courses}

for row in rows[1:]:
    ts = row[idx["ts"]].strip() if len(row) > idx["ts"] else ""
    if "/2026 " not in ts:
        continue

    username = (row[idx["email_username"]] if len(row) > idx["email_username"] else "").strip().lower()
    if not username:
        continue

    if is_yes(row[idx["cp"]] if len(row) > idx["cp"] else ""):
        data["ComplPint26"].append([username, "ComplPint26", "1"])
    if is_yes(row[idx["ma"]] if len(row) > idx["ma"] else ""):
        data["MedAud-26"].append([username, "MedAud-26", "1"])
    if is_yes(row[idx["lv"]] if len(row) > idx["lv"] else ""):
        data["LVI-2026"].append([username, "LVI-2026", "1"])

for course, out_file in courses.items():
    unique = {}
    for r in data[course]:
        unique[(r[0], r[1])] = r
    rows_out = sorted(unique.values(), key=lambda x: x[0])
    with out_file.open("w", encoding="utf-8", newline="") as f:
        w = csv.writer(f)
        w.writerow(["username", "course1", "type1"])
        w.writerows(rows_out)
    print(f"OK: {course}: {len(rows_out)} -> {out_file}")
PY
