#!/usr/bin/env bash
set -euo pipefail

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
INPUT_XLSX="${1:-$BASE_DIR/data/raw/Estudiantes Inscripcion Plataforma.xlsx}"
TMP_DIR="$BASE_DIR/data/.tmp_contactos"
TMP_CSV="$TMP_DIR/Estudiantes Inscripcion Plataforma.csv"
OUTPUT_CSV="${2:-$BASE_DIR/data/processed/gmail/contactos_gmail_2026.csv}"

if ! command -v libreoffice >/dev/null 2>&1; then
  echo "Error: libreoffice no está instalado." >&2
  exit 1
fi

if [[ ! -f "$INPUT_XLSX" ]]; then
  echo "Error: no existe el archivo de entrada: $INPUT_XLSX" >&2
  exit 1
fi

mkdir -p "$TMP_DIR"
mkdir -p "$(dirname "$OUTPUT_CSV")"

libreoffice --headless --convert-to csv --outdir "$TMP_DIR" "$INPUT_XLSX" >/dev/null 2>&1

python3 - "$TMP_CSV" "$OUTPUT_CSV" <<'PY'
import csv
import re
import sys
from pathlib import Path

src = Path(sys.argv[1])
out = Path(sys.argv[2])

header = [
  "Name Prefix",
  "First Name",
  "Middle Name",
  "Last Name",
  "Name Suffix",
  "Phonetic First Name",
  "Phonetic Middle Name",
  "Phonetic Last Name",
  "Nickname",
  "File As",
  "E-mail 1 - Label",
  "E-mail 1 - Value",
  "Phone 1 - Label",
  "Phone 1 - Value",
  "Address 1 - Label",
  "Address 1 - Country",
  "Address 1 - Street",
  "Address 1 - Extended Address",
  "Address 1 - City",
  "Address 1 - Region",
  "Address 1 - Postal Code",
  "Address 1 - PO Box",
  "Organization Name",
  "Organization Title",
  "Organization Department",
  "Birthday",
  "Event 1 - Label",
  "Event 1 - Value",
  "Relation 1 - Label",
  "Relation 1 - Value",
  "Website 1 - Label",
  "Website 1 - Value",
  "Custom Field 1 - Label",
  "Custom Field 1 - Value",
  "Notes",
  "Labels",
]

rows_out = []

with src.open("r", encoding="latin-1", newline="") as f:
  rows = list(csv.reader(f))

for row in rows[1:]:
  ts = row[0].strip() if len(row) > 0 else ""
  if "/2026 " not in ts:
    continue

  nom = (row[4] if len(row) > 4 else "").strip()
  ape = (row[5] if len(row) > 5 else "").strip()
  mail = (row[6] if len(row) > 6 else "").strip().lower()
  mail2 = (row[1] if len(row) > 1 else "").strip().lower()
  wsp = (row[10] if len(row) > 10 else "").strip()

  if not mail:
    mail = mail2
  if not mail:
    continue

  wspr = re.sub(r"[^0-9+]", "", wsp)
  if len(wspr) < 7:
    wspr = ""

  file_as = (f"{ape}, {nom}").strip(", ")
  record = {key: "" for key in header}
  record["First Name"] = nom
  record["Last Name"] = ape
  record["File As"] = file_as
  record["E-mail 1 - Label"] = "Home"
  record["E-mail 1 - Value"] = mail
  if wspr:
    record["Phone 1 - Label"] = "Mobile"
    record["Phone 1 - Value"] = wspr
  record["Labels"] = "2026"
  rows_out.append([record[key] for key in header])

with out.open("w", encoding="utf-8-sig", newline="") as f:
  w = csv.writer(f)
  w.writerow(header)
  w.writerows(rows_out)

print(len(rows_out))
PY

ROWS=$(( $(wc -l < "$OUTPUT_CSV") - 1 ))
echo "OK: generado $OUTPUT_CSV"
echo "Contactos exportados (2026): $ROWS"
