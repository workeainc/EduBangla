# ADR 0001: Foundation Architecture

- Status: Accepted
- Date: 2026-08-27

## Context

EduBangla must operate as a practical Bangladesh high-school pilot and grow to multiple schools without redesigning identity, academic, or result foundations.

## Decision

Use Laravel 12+ on PHP 8.3+ as a modular monolith, with Livewire 3, Alpine.js, Tailwind CSS, MySQL 8+, Redis, Laravel Sanctum, and Spatie Laravel Permission. Organize business capabilities in domain-oriented modules with Actions/Services; UI layers do not calculate academic outcomes. Use shared-database, row-scoped multi-tenancy rooted at `schools`, enforced through tenant context, query scopes, and Policies. Model curricula, assessment structures, and grade rules as configurable data. Keep the result engine isolated and testable.

## Rationale

Laravel offers mature authentication, policies, queues, storage, migrations, testing, and API capabilities with a maintainable path for a small pilot team. Livewire enables a productive server-driven pilot UI without closing off APIs. MySQL is operationally familiar and supports relational integrity. Redis provides production cache and queues. Spatie Permission supplies established role primitives, while Policies enforce record-level/tenant boundaries. Configurable academic rules and a separate result engine prevent Bangladesh-specific rules from becoming untraceable UI code.

## Consequences

Implementation must explicitly carry tenant context, introduce policy and isolation tests early, and avoid direct cross-domain database shortcuts. This is not a microservices commitment; bounded modules are kept in one deployable application until operational evidence justifies extraction. Future government and central analytics integrations require additional ADRs covering governance, privacy, and data exchange.
