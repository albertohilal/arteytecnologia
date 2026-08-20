# Agents & Skills Governance — Design Report

```text
Status:          DESIGN READY FOR IMPLEMENTATION
Date:            2026-08-17
SDD:             SDD_AGENTS_SKILLS_GOVERNANCE
Implementation:  NOT AUTHORIZED
```

> **DOCUMENTATION LOCATION STATUS = CANONICAL**
>
> This report lives at its canonical location `docs/00-INDEX/AGENTS-SKILLS-GOVERNANCE-DESIGN-2026-08-17.md`,
> normalized per `docs/00-INDEX/DOCUMENTATION-RULES.md`. It was moved here from the provisional
> `docs/` root during the IMPLEMENTATION phase of SDD_AGENTS_SKILLS_GOVERNANCE.

---

## 1. Executive Summary

The operational framework of `~/Proyectos/arteytecnologia.com.ar` is currently **memory-only**: there is
**no project-level `AGENTS.md`** and **no local Moodle skills**. Permanent rules, domain procedures, and
transient state live scattered across three external places: the global OpenCode config
(`~/.config/opencode/`), the Engram persistent memory, and (minimally) `README.md` + `.gitignore`.

A prior audit (`AGENTS_SKILLS_AUDIT = COMPLETED`) confirmed that a fresh OpenCode session depends on
`mem_search` to recover safety-critical rules, which is not a reliable enforcement guarantee.

The design proposes a **four-layer governance model**, strictly separating:

1. **AGENTS.md** — permanent agent rules + gates + operational limits.
2. **SKILL.md** (`.opencode/skills/moodle-*/`) — specialized technical procedures.
3. **DOCUMENTATION-RULES.md** (`docs/00-INDEX/`) — documentation governance (structure, naming, archiving).
4. **CURRENT-STATE.md** (`docs/09-HANDOFF/`) — transient operational state.

Engram remains complementary (cross-session memory), but **must not be the sole source of critical rules**.

Implementation remains **NOT AUTHORIZED**. This document is the persistent design evidence for the
DISCOVERY → SPECIFICATION → DESIGN phases.

---

## 1b. Design Rectifications (2026-08-17)

1. **Documentation taxonomy** — fixed numeric taxonomy with Moodle-specific categories (section 9).
2. **Naming** — canonical paths use hyphens: `docs/00-INDEX/DOCUMENTATION-RULES.md`, `docs/09-HANDOFF/CURRENT-STATE.md`.
3. **Skill registry** — `.opencode/skills/` is natively indexed by the registry generator (section 5).

## 1c. Final Rectifications (applied before implementation)

1. Naming conventions → per-class (folder / governance / report / skill / special filenames).
2. Archive suffix `_vX.Y` → `-vX.Y` (no underscores).
3. Moodle DB prefix → `DETECT_FROM_CONFIG`, never assume `mdl_`.
4. Gate names → five canonical gates; `GATE_DESTRUCTIVE` removed.
5. SDD conventions → `single-PR` / `400-line budget` NOT adopted (no prior canonical rule).
6. Production deploy verification → rollback "validated and locally rehearsed when feasible" (never a production rollback demo).
7. Grading API → prefer official gradebook APIs; direct SQL write only by justified exception.
8. Security evidence → `TEMPORARILY_PRESERVED` + durable copy `PENDING`.

---

## 2. Repository Baseline

```text
BRANCH   = feature/copia-local-moodle
HEAD     = 809ba63e2334528901b44163e98ea8cd9a2b0afb
WORKTREE = DIRTY
             · modified:    moodle/reportes/reporteTPporCurso.php  (uncommitted)
             · untracked:   .atl/ , AUXILIAR/
REMOTE   = github-viejo → https://github.com/albertohilal/arteytecnologia.git
           (local branch ahead of remote by 1 commit)
```

No credentials are recorded here or anywhere in this document.

---

## 3. Discovery Findings

- **Project structure**: `moodle/`, `moodledata/`, `db-dumps/`, `docker/`, `docs/`, `scripts/`, `plugin/`,
  `.atl/`, `README.md`, `docker-compose.yml`, `.gitignore`.
- **No project `AGENTS.md`**: `find` for `AGENTS.md`/`AGENT.md` returned nothing at project level.
- **No local skills**: no `SKILL.md` anywhere in the project; no `skills/` directory; no `.opencode/`.
- **Global config found** (`~/.config/opencode/`):
  - `AGENTS.md` (generic orchestrator protocol; zero Moodle content).
  - `opencode.json` — top-level `$schema`, `agent`, `mcp`, `share`; 19 agents; no `skills` key.
  - `skills/` — SDD skills + `judgment-day` + `_shared`; no Moodle skills.
  - `commands/`, `plugins/skill-registry.ts` (startup registry refresh).
- **Gentle-Orchestrator mechanism**: delegates via `task`; resolves skills via Skill Resolver Protocol
  (`_shared/skill-resolver.md`) using the registry.
- **Engram**: holds the actual project knowledge (period-grading, security remediation, evidence, SDD prefs).
- **`.atl/skill-registry.md`**: auto-generated index; currently lists only `judgment-day`.
- **No persistent handoff**: `docs/` empty (`.gitkeep` only); no taxonomy, no archive policy.

---

## 4. Discovery Rectifications

```text
Docker = INSTALLED_AND_WORKING
```

```text
arteytecnologia_web          = UP   (0.0.0.0:8080->80/tcp)
arteytecnologia_db           = UP   (3306/tcp)
arteytecnologia_phpmyadmin   = UP   (0.0.0.0:8081->80/tcp)

http://localhost:8080 = WORKING   (Moodle local)
http://localhost:8081 = WORKING   (phpMyAdmin)
```

The earlier claim "Docker is not installed" is **withdrawn**. `moodledata` is mounted as a Docker volume.

---

## 5. Skill Discovery Mechanism

Evidence: `customize-opencode` skill (authoritative), `opencode.json`, `plugins/skill-registry.ts`, and the
`gentle-ai` source at `internal/skillregistry/registry.go`.

```text
SKILL_DISCOVERY_PATHS   = Global:   ~/.config/opencode/skills/<name>/SKILL.md
                          Project:  .opencode/skills/<name>/SKILL.md
                          External: ~/.claude/skills/, ~/.agents/skills/
PROJECT_LOCAL_SKILL_PATH = .opencode/skills/<name>/SKILL.md
GLOBAL_SKILL_PATH        = ~/.config/opencode/skills/<name>/SKILL.md
REGISTRY_UPDATE_REQUIRED = YES  (regenerate, not hand-edit)
AUTO_DISCOVERY_SUPPORTED = YES  (native loader scans **/SKILL.md; requires restart)
SKILL_PRECEDENCE         = project skills preferred over user-global on name collision
```

**Registry mechanism (definitive, from `registry.go`):**

```text
REGISTRY_PROJECT_SKILLS_SUPPORTED    = YES
REGISTRY_PROJECT_SKILLS_CONFIGURATION = NONE REQUIRED   (scan paths hardcoded in ProjectSkillDirs)
REGISTRY_GENERATOR_CHANGE_REQUIRED   = NO
REGISTRY_MANUAL_EDIT_REQUIRED        = NO
RESTART_REQUIRED                     = YES (native loader); registry via `refresh --force` or next startup
```

Facts:

- `ProjectSkillDirs(cwd)` includes `cwd/.opencode/skills` (plus `cwd/skills`, `cwd/.atl/skills`, and other
  agent-native dirs). `UserSkillDirs(home)` includes `~/.config/opencode/skills`.
- `Regenerate` scans both project + user dirs (existing dirs only), one level deep for `<root>/<skill>/SKILL.md`.
- Exclusion policy: `_shared`, `skill-registry`, and any `sdd-*` prefix. **`moodle-*` is NOT excluded.**
- `dedupeBySkillName` **prefers project-level over user-level**; `ScopeForPath` tags each entry `project`/`user`.
- `refresh` flags: `--force|-f`, `--quiet|-q`, `--no-gitignore`, `--cwd <path>`.
- The current registry lists only `judgment-day` because at last generation **no project skill dir existed**;
  once `.opencode/skills/moodle-*/` exists, `gentle-ai skill-registry refresh --force` indexes them automatically.

**Conclusion:** `.opencode/skills/` satisfies **both** loaders with zero tooling change and zero config:
1. **OpenCode native loader** (project skill location, surfaces in `available_skills`).
2. **gentle-ai registry generator** (`ProjectSkillDirs` includes `.opencode/skills`).

The registry must be **regenerated**, not manually edited (auto-generated file). This satisfies the
preference to avoid modifying global OpenCode tooling.

---

## 6. Specification — Four Layers

```text
AGENTS.md               = permanent rules + gates + operational limits
SKILL.md                = specialized technical procedures
DOCUMENTATION-RULES.md  = documentation governance (structure, naming, archive, classification)
CURRENT-STATE.md        = transient operational state
```

**Engram** plays a complementary role: cross-session memory and SDD artifact persistence. It is **not** the
single source of truth for critical rules — those must live in files OpenCode loads (AGENTS.md) and in domain
skills (SKILL.md), so a fresh session does not depend solely on `mem_search`.

---

## 7. AGENTS.md Design

Proposed content outline for a project-level `AGENTS.md`:

- Gentle-Orchestrator = principal agent.
- `MODE = SDD` (mandatory).
- `DESIGN != IMPLEMENTATION`.
- `NO AUTHORIZATION = NO EXECUTION`.
- `LOCAL FIRST`.
- Local / production separation (production is not a test environment).
- Git as evidence of the real state.
- Protection of pre-existing local changes.
- Protection of Moodle core (avoid core edits; prefer APIs, plugins, config, `moodle/reportes/`).
- Critical data: Moodle DB, `moodledata`, grades, submissions, users, course files.
- Gates (canonical — one name per operation type):
  `GATE_SDD_DESTRUCTIVE_OPERATION`, `GATE_DATABASE_CHANGE`, `GATE_PRODUCTION_DEPLOYMENT`,
  `GATE_SDD_SECURITY_QUARANTINE`, `GATE_MOODLE_PRODUCTION_CUTOVER` — all `AWAITING_AUTHORIZATION` by default.
- Security: production has a pending incident and must not be assumed clean.
- Deployments: production deployment requires explicit human authorization.
- Git prohibitions without authorization: no `reset`, `restore`, `clean`, branch switch, `commit`, `push`,
  `merge`, `rebase`.
- Environment identification: `http://localhost:8080`, phpMyAdmin `http://localhost:8081`,
  production `https://arteytecnologia.com.ar/`.
- Project SDD conventions: Gentle-Orchestrator, `MODE = SDD`, Engram complementary, LOCAL FIRST, GATES,
  E2E verification, Git protection, critical-data protection.
  `SINGLE_PR_POLICY = NOT_DEFINED_FOR_MOODLE` · `LINE_BUDGET_POLICY = NOT_DEFINED_FOR_MOODLE`
  (no canonical prior rule approves single-PR or a 400-line budget for this project — not invented here).

---

## 8. Skills Design

Decision: `SDD_WORKFLOW_SKILL = NOT_REQUIRED` (covered by global `sdd-*` skills + project `AGENTS.md`).

### moodle-grading

```text
PATH            = .opencode/skills/moodle-grading/SKILL.md
PURPOSE         = Read/compute/persist course period grades in the Moodle gradebook safely.
RESPONSIBILITY  = Students, activities, grade_items, grade_grades, gradebook, read, calculate, write,
                  overrides, preservation of existing grades.
TRIGGERS        = grading, grade, calificaciones, nota, cuatrimestre, período, gradebook, reporteTPporCurso.
CONTENT_OUTLINE = LOGIN → CURSO → FORMULARIO → LECTURA → CÁLCULO → GUARDADO → DATABASE → GRADEBOOK → RELECTURA;
                  prefer official Moodle gradebook APIs; avoid direct SQL writes to grade_items/grade_grades;
                  direct SQL allowed for read-only diagnosis only; any exception must be justified and pass
                  DESIGN + authorization; always verify preservation of existing grades.
GATES           = destructive grade change stops at GATE_DATABASE_CHANGE.
VERIFICATION    = end-to-end read-back (DB → gradebook → re-read).
SHOULD_NOT_CONTAIN = policy, secrets, current state.
```

### moodle-database

```text
PATH            = .opencode/skills/moodle-database/SKILL.md
PURPOSE         = Backup, dump, restore, import of the local Moodle DB.
RESPONSIBILITY  = Table prefixes, integrity, DB↔moodledata relationship, import-safety.
TRIGGERS        = database, dump, backup, restore, import, migración de datos, mysqldump, phpMyAdmin.
CONTENT_OUTLINE = backup/dump/restore/import; prefix detection; DB↔moodledata sync; import-safety checks.
MOODLE_TABLE_PREFIX = DETECT_FROM_CONFIG   (read $CFG->prefix from config.php; verify against the real
                  target tables; never assume mdl_; stop if config.php and real tables mismatch;
                  applies to local AND production).
GATES           = import over existing base stops at GATE_DATABASE_CHANGE = AWAITING_AUTHORIZATION.
VERIFICATION    = row/table count + gradebook spot-check after restore.
SHOULD_NOT_CONTAIN = credentials, policy, current state.
```

### moodle-local-docker

```text
PATH            = .opencode/skills/moodle-local-docker/SKILL.md
PURPOSE         = Operate the local Docker stack safely.
RESPONSIBILITY  = Knows arteytecnologia_web / arteytecnologia_db / arteytecnologia_phpmyadmin; moodledata critical.
TRIGGERS        = docker, contenedor, compose, localhost:8080, localhost:8081, moodledata.
CONTENT_OUTLINE = compose lifecycle; volume mapping; phpMyAdmin; prohibited destructive operations.
GATES           = docker compose down -v / volume rm / prune --volumes require GATE_SDD_DESTRUCTIVE_OPERATION.
VERIFICATION    = docker compose ps; localhost:8080/8081 reachable.
SHOULD_NOT_CONTAIN = policy, secrets, current state.
```

### moodle-production-deploy

```text
PATH            = .opencode/skills/moodle-production-deploy/SKILL.md
PURPOSE         = Plan and (only after authorization) execute a production deployment.
RESPONSIBILITY  = Enforce the full pre-deployment checklist.
TRIGGERS        = deploy, producción, release, production, rollout.
CONTENT_OUTLINE = FILES_TO_DEPLOY / DATABASE_CHANGES / BACKUP_REQUIRED / RISKS / ROLLBACK_PLAN / VERIFICATION_PLAN.
GATES           = GATE_PRODUCTION_DEPLOYMENT = AWAITING_AUTHORIZATION before production;
                  GATE_MOODLE_PRODUCTION_CUTOVER = AWAITING_AUTHORIZATION for the final cutover.
VERIFICATION    = rollback plan validated and locally rehearsed when feasible; post-deploy smoke checks.
                  (never execute a rollback in production merely to demonstrate the procedure exists).
SHOULD_NOT_CONTAIN = credentials, current state.
```

### moodle-security

```text
PATH            = .opencode/skills/moodle-security/SKILL.md
PURPOSE         = Discover, classify, quarantine, and remediate security findings.
RESPONSIBILITY  = Evidence preservation before any move/delete; phase separation.
TRIGGERS        = security, malware, webshell, incidente, cuarentena, quarantine, remediación.
CONTENT_OUTLINE = CONFIRMED_MALICIOUS / HIGHLY_SUSPICIOUS / SUSPICIOUS / LEGITIMATE / UNKNOWN;
                  DISCOVERY / QUARANTINE / REMEDIATION / PRODUCTION INCIDENT; evidence manifest (SHA256).
GATES           = GATE_SDD_SECURITY_QUARANTINE = AWAITING_AUTHORIZATION before quarantine/removal.
VERIFICATION    = evidence manifest integrity; no action without prior evidence snapshot.
SHOULD_NOT_CONTAIN = secrets, current state, raw payloads.
```

---

## 9. Documentation Governance Design

```text
DOCUMENTATION_RULES_REQUIRED = YES
```

**This is a proposal, NOT yet implemented.** No folders are created now. Once approved, the structure
becomes **FIXED** (numeric taxonomy, LeadMaster principle adapted to Moodle domains).

```text
DOCUMENTATION_TAXONOMY           = docs/
                                     ├── 00-INDEX/           → index + DOCUMENTATION-RULES.md (norm)
                                     ├── 01-GOBERNANZA/      → governance framework + decisions
                                     ├── 02-ARQUITECTURA/    → technical decisions / ADR
                                     ├── 03-INFRAESTRUCTURA/ → docker, server, deployment
                                     ├── 04-MOODLE/          → Moodle domain (grading, gradebook, reporteTP)
                                     ├── 05-REPORTES/        → dated reports (YYYY-MM)
                                     ├── 06-SDD/             → SDD file artifacts (if file-based required)
                                     ├── 07-SEGURIDAD/       → incidents, evidence, remediation
                                     ├── 08-MIGRACIONES/     → SSD→local, prod→local, data migrations
                                     ├── 09-HANDOFF/         → transient state (CURRENT-STATE.md)
                                     └── 99-ARCHIVO/         → obsolete / superseded

DOCUMENTATION_RULES_PATH          = docs/00-INDEX/DOCUMENTATION-RULES.md
CURRENT_STATE_PATH                = docs/09-HANDOFF/CURRENT-STATE.md

NAMING_CONVENTION                = per-class (explicit), no spaces, no underscores in new governed docs:
                                   TAXONOMY_FOLDER_NAMING     = NN-UPPER-KEBAB-CASE   (00-INDEX, 01-GOBERNANZA, …)
                                   GOVERNANCE_DOCUMENT_NAMING = UPPER-KEBAB-CASE      (DOCUMENTATION-RULES.md, CURRENT-STATE.md)
                                   REPORT_DOCUMENT_NAMING     = UPPER-KEBAB-CASE + date YYYY-MM-DD
                                   SKILL_DIRECTORY_NAMING     = lowercase-kebab-case  (.opencode/skills/moodle-grading/)
                                   SPECIAL_FILENAMES          = AGENTS.md, README.md, SKILL.md
                                                                (tool/project convention — exempt from the general rules)
REPORT_CONVENTION                = docs/05-REPORTES/YYYY-MM/<NAME>-YYYY-MM-DD.md
                                   (e.g. DIAGNOSTICO-GRADEBOOK-2026-08-17.md) + mandatory header Fecha: YYYY-MM-DD
SDD_DOCUMENT_LOCATION            = Engram (topic sdd/{change}/...) by default; if file-based required → docs/06-SDD/
SECURITY_DOCUMENT_LOCATION       = docs/07-SEGURIDAD/   (raw evidence out of repo, referenced by path; no secrets)
INFRASTRUCTURE_DOCUMENT_LOCATION = docs/03-INFRAESTRUCTURA/
MIGRATION_DOCUMENT_LOCATION      = docs/08-MIGRACIONES/
HANDOFF_DOCUMENT_LOCATION        = docs/09-HANDOFF/CURRENT-STATE.md   (only file holding transient state)
ARCHIVE_POLICY                   = superseded/obsolete docs move to docs/99-ARCHIVO/ with -vX.Y suffix
                                   (e.g. DIAGNOSTICO-GRADEBOOK-v2.0.md); never mix obsolete with current;
                                   never delete (preserve evidence, especially security)
```

Equivalent structural rules (all incorporated):

- No new document outside the official structure.
- No new categories without review/approval.
- Every generated document states its destination path.
- Reports carry a date (YYYY-MM-DD).
- Obsolete documentation is archived, never mixed with current.
- Temporary operational documentation is never confused with normative documentation.
- AI-generated documents are subject to the same rules.

The LeadMaster `DOCUMENTATION_RULES.md` model is used **only as a conceptual reference** for the
*fixed numeric taxonomy* principle; its business categories are **not** copied.

---

## 10. Handoff / Current State Design

Target file: `docs/09-HANDOFF/CURRENT-STATE.md`.

```text
PROJECT             = arteytecnologia.com.ar (Moodle local)
SDD                 = SDD_AGENTS_SKILLS_GOVERNANCE   (and active change, when applicable)
PHASE               = current SDD phase
UNIT                = current work unit
GATE                = current gate + status
BRANCH              = (current git branch)
HEAD                = (current git HEAD)
WORKTREE            = (dirty/clean summary, uncommitted changes)
LAST_VERIFIED_STATE = timestamp of last confirmed environment/state check
NEXT_ACTION         = next recommended action
NOT_AUTHORIZED      = list of pending authorizations
RISKS               = current active risks
```

Explicit rule:

```text
CONTAINS_SECRETS = NO
```

Credentials are never written; a local credential is referenced only as
`LOCAL_ADMIN_CREDENTIAL = CONFIGURED_NOT_STORED`.

---

## 11. Current Operational Context

Temporal context (clearly separated from permanent rules; NOT to be embedded in `AGENTS.md`):

```text
SDD_MOODLE_PERIOD_GRADING  = IN_PROGRESS
VERIFICATION               = VERIFIED_LOCAL_PARTIAL

SDD_MOODLE_SECURITY_REMEDIATION = SUSPENDED
PHASE                      = DISCOVERY_COMPLETED
SECURITY_EVIDENCE          = TEMPORARILY_PRESERVED
SECURITY_EVIDENCE_DURABLE_COPY = PENDING
```

Known period-grading blockers (no secrets):

- `courseid=15` has `grade_forum=0`, blocking the period-grade calculation.
- The local database comes from a dump that predates the latest grades loaded in production.

Note: security evidence currently lives exclusively under `/tmp/opencode/moodle-recovery-evidence/`
(temporary). A durable, out-of-repo copy referenced by manifest is PENDING a future authorized
preservation step.

---

## 12. Proposed File Plan

Reflects the DESIGN only — **none of these changes have been performed yet**:

```text
FILES_TO_CREATE = AGENTS.md
                  .opencode/skills/moodle-grading/SKILL.md
                  .opencode/skills/moodle-database/SKILL.md
                  .opencode/skills/moodle-local-docker/SKILL.md
                  .opencode/skills/moodle-production-deploy/SKILL.md
                  .opencode/skills/moodle-security/SKILL.md
                  docs/00-INDEX/DOCUMENTATION-RULES.md
                  docs/09-HANDOFF/CURRENT-STATE.md
                  (taxonomy folders 00-INDEX … 99-ARCHIVO created with DOCUMENTATION-RULES.md)

FILES_TO_MODIFY = README.md
                  .atl/skill-registry.md   (REGENERATED via `gentle-ai skill-registry refresh --force`,
                                            NOT hand-edited)

FILES_TO_REMOVE = (none)
```

Note: this design report (`docs/AGENTS-SKILLS-GOVERNANCE-DESIGN-2026-08-17.md`) is the only file
authorized and created so far.

---

## 13. Risks

- **Pre-existing local changes**: `moodle/reportes/reporteTPporCurso.php` is modified and uncommitted.
- **Skill registry regeneration**: `.atl/skill-registry.md` is auto-generated; must be regenerated, not hand-edited.
- **OpenCode reload/restart required**: skills are loaded at startup; invisible until restart.
- **Global/local rule duplication**: project AGENTS.md must not restate (or contradict) the global protocol.
- **Transient state as policy**: current state must never leak into AGENTS.md.
- **Secret persistence**: credentials never versioned; only `CONFIGURED_NOT_STORED`.
- **Pending security incident**: production is not clean; security discipline remains active.
- **Security evidence pending permanent preservation**: evidence under `/tmp/opencode/moodle-recovery-evidence/`
  is temporary (`TEMPORARILY_PRESERVED`) and needs a durable home (out of repo, referenced by manifest).

---

## 14. Verification Criteria for Future Implementation

After implementation, verify that:

1. OpenCode loads the project `AGENTS.md` (visible in session instructions).
2. OpenCode discovers the Moodle skills (appear in `available_skills`; invoke via `skill` tool).
3. Each skill is selected correctly for its matching task (trigger matching).
4. `LOCAL FIRST` is respected (no production mutation without authorization).
5. The agent stops at every GATE and requests human authorization.
6. A new session can recover current state from files, not Engram alone.
7. No critical rule depends solely on Engram.
8. No secrets are present in any versioned file (`grep` audit + `git diff`).
9. `gentle-ai skill-registry list` shows the five `moodle-*` skills with scope `project`.

---

## 15. Gate Status

```text
AGENTS_SKILLS_AUDIT   = COMPLETED
AGENTS_SKILLS_DESIGN  = READY_FOR_IMPLEMENTATION

DOCUMENTATION_REPORT  = CREATED

IMPLEMENTATION        = NOT_AUTHORIZED

GATE_AGENTS_SKILLS_IMPLEMENTATION =
AWAITING_AUTHORIZATION
```
