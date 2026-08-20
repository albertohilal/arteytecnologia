# Documentation Rules

```text
Version:      1.0
Status:       Official Norm
Last Updated: 2026-08-17
```

---

## Official Structure (FIXED)

The numeric folder structure under `docs/` is **FIXED and IMMUTABLE**.

```
docs/
├── 00-INDEX/           → Index + documentation rules (this norm)
├── 01-GOBERNANZA/      → Governance framework and decisions
├── 02-ARQUITECTURA/    → Technical decisions / ADR
├── 03-INFRAESTRUCTURA/ → Docker, server, deployment
├── 04-MOODLE/          → Moodle domain (grading, gradebook, reporteTP)
├── 05-REPORTES/        → Dated reports (YYYY-MM)
├── 06-SDD/             → SDD file artifacts (when file-based is required)
├── 07-SEGURIDAD/       → Incidents, evidence, remediation
├── 08-MIGRACIONES/     → Migration notes (SSD→local, prod→local, data migrations)
├── 09-HANDOFF/         → Transient state (CURRENT-STATE.md)
└── 99-ARCHIVO/         → Obsolete / superseded
```

## Structural Integrity Rule

- The numeric folder structure is **FIXED**.
- **No new numbered folders** may be created without review/approval.
- New documents must live within an existing folder.
- Temporary operational documentation must never be confused with normative documentation.

---

## Naming Conventions (per class)

No spaces and no underscores in new governed documents.

```text
TAXONOMY_FOLDER_NAMING      = NN-UPPER-KEBAB-CASE
                              (00-INDEX, 01-GOBERNANZA, 03-INFRAESTRUCTURA, 09-HANDOFF, 99-ARCHIVO)

GOVERNANCE_DOCUMENT_NAMING  = UPPER-KEBAB-CASE
                              (DOCUMENTATION-RULES.md, CURRENT-STATE.md)

REPORT_DOCUMENT_NAMING      = UPPER-KEBAB-CASE + date YYYY-MM-DD
                              (DIAGNOSTICO-GRADEBOOK-2026-08-17.md)

SKILL_DIRECTORY_NAMING      = lowercase-kebab-case
                              (.opencode/skills/moodle-grading/)

SPECIAL_FILENAMES           = AGENTS.md, README.md, SKILL.md
                              (tool/project convention — exempt from the general rules)
```

---

## Report Convention

```text
docs/05-REPORTES/YYYY-MM/<NAME>-YYYY-MM-DD.md
```

Every report must carry a date header: `Fecha: YYYY-MM-DD`.

---

## Archive Policy

Superseded or obsolete documents move to `docs/99-ARCHIVO/` with a `-vX.Y` suffix:

```text
DIAGNOSTICO-GRADEBOOK-v2.0.md
```

- Never mix obsolete with current.
- Never delete (preserve evidence, especially security).
- Never use underscores (`_vX.Y`) in the version suffix.

---

## Mandatory Rule

Every generated document must:

1. Explicitly indicate its destination path.
2. Be saved directly in the corresponding folder.
3. Include a date if it is a report (`YYYY-MM-DD`).
4. Not create new folders without approval.

---

## AI Compliance Rule

AI-generated documents are subject to the same rules:

- The target folder must be explicitly specified.
- The file name must follow the naming convention.
- The AI must not propose alternative folder structures.

---

## Governance Principle

- `AGENTS.md` defines **agent rules + gates** (what the agent may/may not do).
- `SKILL.md` defines **specialized technical procedures** (how to do a task).
- `DOCUMENTATION-RULES.md` (this file) defines **documentation governance** (structure, naming, archive).
- `CURRENT-STATE.md` defines **transient operational state** (where the project is now).

They must never duplicate each other; they reference each other instead.
