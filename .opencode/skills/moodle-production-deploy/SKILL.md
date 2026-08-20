---
name: moodle-production-deploy
description: "Plan and (only after authorization) execute a production deployment. Use for deploy, producción, release, production, rollout."
---

# Moodle Production Deploy

Specialized procedure for planning and — only after explicit authorization — executing a production
deployment.

## Responsibility

Enforce the full pre-deployment checklist before anything reaches production.

## Pre-deployment checklist (required)

```text
FILES_TO_DEPLOY   = (exact list)
DATABASE_CHANGES  = (exact list)
BACKUP_REQUIRED   = (what and where)
RISKS             = (known risks)
ROLLBACK_PLAN     = (validated and locally rehearsed when feasible)
VERIFICATION_PLAN = (post-deploy checks)
```

## Gates

```text
GATE_PRODUCTION_DEPLOYMENT       = AWAITING_AUTHORIZATION  (before any production change)
GATE_MOODLE_PRODUCTION_CUTOVER   = AWAITING_AUTHORIZATION  (final cutover switch to production)
```

## Rollback rule (critical)

The rollback plan must be **validated and locally rehearsed when feasible**. Never execute a real
rollback in production merely to demonstrate that the procedure exists.

## Verification

Post-deploy smoke checks per `VERIFICATION_PLAN`. No production mutation without both gates cleared.

## Should NOT contain

Credentials, current state.
