---
name: moodle-security
description: "Discover, classify, quarantine, and remediate security findings. Use for security, malware, webshell, incidente, cuarentena, quarantine, remediación."
---

# Moodle Security

Specialized procedure for discovering, classifying, quarantining, and remediating security findings.

## Responsibility

Preserve evidence before any move/delete, and keep the security phases strictly separated.

## Classification (canonical)

```text
CONFIRMED_MALICIOUS
HIGHLY_SUSPICIOUS
SUSPICIOUS
LEGITIMATE
UNKNOWN
```

## Phases (separated)

```text
DISCOVERY
QUARANTINE
REMEDIATION
PRODUCTION INCIDENT
```

## Evidence preservation

Before moving or deleting any file, snapshot it and record a SHA256 manifest. Do not act on
unclassified or un-evidenced files.

## Gates

```text
GATE_SDD_SECURITY_QUARANTINE = AWAITING_AUTHORIZATION  (before quarantine/removal)
```

## Verification

Evidence manifest integrity; no action without a prior evidence snapshot.

## Should NOT contain

Secrets, current state, raw payloads.
