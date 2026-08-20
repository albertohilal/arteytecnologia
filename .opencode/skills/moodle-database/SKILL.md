---
name: moodle-database
description: "Backup, dump, restore, and import the local Moodle database safely. Use for database, dump, backup, restore, import, migración de datos, mysqldump, phpMyAdmin."
---

# Moodle Database

Specialized procedure for backing up, dumping, restoring, and importing the local Moodle database.

## Responsibility

Table prefixes, integrity, DB ↔ `moodledata` relationship, and protection against destructive import.

## Table Prefix Policy (critical)

```text
MOODLE_TABLE_PREFIX = DETECT_FROM_CONFIG
```

- Read `$CFG->prefix` from `config.php` (do not guess).
- Verify the real prefix of the target database against its actual tables.
- **Never assume `mdl_`.**
- **Stop** if `config.php` and the real tables do not match.
- Applies to **local AND production**.

## Procedure

1. Identify the target DB and its real table prefix (`DETECT_FROM_CONFIG`).
2. Backup / dump: `mysqldump` (or phpMyAdmin export) with the detected prefix; store in `db-dumps/`.
3. Verify DB ↔ `moodledata` relationship before any restore (files and DB must stay in sync).
4. Restore / import: never import over an existing base without authorization.

## Gates

Any import over an existing base stops at:

```text
GATE_DATABASE_CHANGE = AWAITING_AUTHORIZATION
```

## Verification

Row/table count plus a gradebook spot-check after every restore.

## Should NOT contain

Credentials, policy, current state.
