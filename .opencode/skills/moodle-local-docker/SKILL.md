---
name: moodle-local-docker
description: "Operate the local Docker stack safely. Use for docker, contenedor, compose, localhost:8080, localhost:8081, moodledata."
---

# Moodle Local Docker

Specialized procedure for operating the local Docker stack safely.

## Responsibility

Knows the local containers and their volumes:

```text
arteytecnologia_web        (Moodle, http://localhost:8080)
arteytecnologia_db         (MariaDB 10.5)
arteytecnologia_phpmyadmin (http://localhost:8081)
```

`moodledata` is a named Docker volume and is **critical** — it must never be lost.

## Prohibited without authorization

```text
docker compose down -v
docker volume rm
docker system prune --volumes
```

## Gates

Any destructive volume/container operation stops at:

```text
GATE_SDD_DESTRUCTIVE_OPERATION = AWAITING_AUTHORIZATION
```

## Procedure

1. `docker compose ps` to check container state (all three should be `Up`).
2. Use `docker compose up -d` / `down` (without `-v`) for normal lifecycle.
3. Verify `http://localhost:8080` and `http://localhost:8081` are reachable.

## Verification

`docker compose ps` + reachability of localhost:8080/8081.

## Should NOT contain

Policy, secrets, current state.
