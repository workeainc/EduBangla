# ADR 0015: Parent Identity Boundary

- Status: Decision required — parent access remains blocked
- Date: 2026-08-30

## Context

The pilot scope names a parent portal, but `Guardian` is a school-owned profile
linked to students rather than an authenticated `User` with an active
school-local membership. The Communication boundary explicitly defers parent
delivery for this reason. No parent route group exists.

## Decision gate

Parent access must not be implemented until an approved ADR defines the full
relationship:

`Guardian ↔ authenticated User ↔ school membership`

The approval must specify linking authority and consent, multi-school identity
behavior, revocation, child visibility, audit requirements, account recovery,
and protection against cross-school or cross-child disclosure.

Communication must not be used as a shortcut to establish parent identity or
delivery authority.

## Current decision

**Parent portal remains blocked until Guardian ↔ authenticated User ↔ school membership identity is explicitly designed and approved.**

This decision adds no routes, migrations, provider delivery or parent-facing
UI. A future implementation requires focused threat-model and tenant-isolation
tests before acceptance.
