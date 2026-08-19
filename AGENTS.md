# AGENTS.md — arteytecnologia.com.ar

<!--
  Permanent operational rules for this project. Loaded by OpenCode.
  This file contains POLICY ONLY. No transient state, no secrets.
  Transient state lives in docs/09-HANDOFF/CURRENT-STATE.md.
-->

## Principal Agent

`Gentle-Orchestrator` = principal agent. It coordinates; executor phase agents do the work.

## Methodology

```text
MODE = SDD

DISCOVERY
→ SPECIFICATION
→ DESIGN
→ AUTHORIZATION
→ IMPLEMENTATION
→ VERIFICATION
→ CLOSURE

DESIGN != IMPLEMENTATION
NO AUTHORIZATION = NO EXECUTION
```

## LOCAL FIRST

All work is done on the local environment first. Never treat production as a test environment.

## Environments

```text
Proyecto:   ~/Proyectos/arteytecnologia.com.ar
Moodle local:  http://localhost:8080
phpMyAdmin:    http://localhost:8081
Producción:    https://arteytecnologia.com.ar/   (NOT a test environment)
```

## Critical Data

Protected data — never modified destructively without authorization:

- Moodle DB
- `moodledata`
- calificaciones (grades)
- entregas (submissions)
- usuarios (users)
- archivos de cursos (course files)

## Canonical Gates

One name per operation type. All default to `AWAITING_AUTHORIZATION`:

```text
GATE_SDD_DESTRUCTIVE_OPERATION   — destructive filesystem/volume/container operations
GATE_DATABASE_CHANGE             — any DB import/replace/restore over an existing base
GATE_PRODUCTION_DEPLOYMENT       — production deployment authorization
GATE_SDD_SECURITY_QUARANTINE     — quarantine/removal of security findings
GATE_MOODLE_PRODUCTION_CUTOVER   — final cutover switch to production
GATE_GIT_CHECKPOINT              — Git checkpoint (commit/push) required to CLOSE important changes
```

Do not invent alternate gate names for the same operation.

## Git Protection

Without authorization, NEVER run:

```text
git reset
git restore
git clean
git switch / checkout (branch change)
git commit
git push
git merge
git rebase
```

Git is the evidence of the real state. Never discard pre-existing local changes.

## Mandatory Git Checkpoint After Important Changes

Every important project change requires a verified Git checkpoint before the change
may be formally CLOSED.

```text
IMPORTANT_CHANGE_REQUIRES_GIT_CHECKPOINT = YES
```

An IMPORTANT_CHANGE includes at least:

- functional code changes;
- database changes or data migrations;
- infrastructure or Docker changes;
- architecture/configuration changes;
- security remediation;
- important SDD unit completion;
- relevant specification/design/implementation rectifications;
- production deployment preparation or completion.

Required lifecycle:

```text
IMPLEMENT
→ VERIFY
→ DOCUMENT_CURRENT_STATE
→ PREPARE_GIT_CHECKPOINT
→ USER_AUTHORIZATION_FOR_COMMIT_PUSH
→ COMMIT
→ PUSH
→ VERIFY_LOCAL_HEAD_EQUALS_REMOTE_HEAD
→ CLOSE
```

A successful implementation or verification alone is NOT sufficient to mark an
important change `CLOSED_SUCCESS`.

Until the Git checkpoint is completed:

```text
IMPORTANT_CHANGE_STATUS = AWAITING_GIT_CHECKPOINT
```

### Commit / Push Authorization

`git commit` and `git push` always require explicit user authorization.

An authorization to implement a UNIT does NOT implicitly authorize commit or push.

Before requesting authorization, report:

```text
FILES_TO_COMMIT =
FILES_EXCLUDED =
SECRET_SCAN_RESULT =
GIT_DIFF_STAT =
GIT_DIFF_REVIEW =
COMMIT_MESSAGE_PROPOSED =
GIT_BRANCH =
LOCAL_HEAD =
REMOTE =
```

### Safe Staging

Never stage the whole worktree blindly.

Without specific review, do NOT use:

```text
git add .
git add -A
```

Stage only the explicitly reviewed files:

```text
git add -- <file1> <file2> ...
```

Untracked files, backups, dumps, `AUXILIAR/`, temporary evidence and sensitive data
must never be included automatically.

### Post-Push Verification

After an authorized commit and push, verify:

```text
LOCAL_HEAD =
REMOTE_HEAD =
LOCAL_HEAD_EQUALS_REMOTE_HEAD = YES
```

Only after this verification may the important change move to:

```text
CLOSED_SUCCESS
```

### Database Changes

Git does NOT back up Moodle database state.

For important database changes:

```text
DB_CHANGE_CHECKPOINT =
PRE_CHANGE_BACKUP
→ AUTHORIZED_DATABASE_CHANGE
→ DATABASE_VERIFICATION
→ DOCUMENT_RESULT
→ GIT_CHECKPOINT_OF_CODE_AND_DOCUMENTATION
```

Database backups/dumps must not be committed unless explicitly designed and
authorized for that purpose.

The Git checkpoint preserves code/documentation/evidence; the database backup
preserves database state.

## Moodle Core

Avoid modifying Moodle core. Prefer official APIs, plugins, configuration, and `moodle/reportes/`.

Project-owned code: `moodle/reportes/reporteTPporCurso.php`.

## Security

Production has a pending security incident and must NOT be assumed clean. Preserve evidence before
any move/delete. No credentials, tokens, or keys may ever be committed or written to files.

## Deployment

Production deployment requires explicit human authorization and the full pre-deployment checklist
(see `moodle-production-deploy` skill).

## SDD Conventions (project)

- Gentle-Orchestrator = principal agent.
- `MODE = SDD`.
- Engram is complementary (cross-session memory); it is NOT the sole source of critical rules.
- LOCAL FIRST, GATES, end-to-end verification, Git protection, critical-data protection.

```text
SINGLE_PR_POLICY   = NOT_DEFINED_FOR_MOODLE
LINE_BUDGET_POLICY = NOT_DEFINED_FOR_MOODLE
```

These are not defined here; do not assume a single-PR or a fixed line-budget rule for this project.

## Skills

Domain procedures live in `.opencode/skills/moodle-*/SKILL.md` (see the skill registry).
