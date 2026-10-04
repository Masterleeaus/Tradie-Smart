<div align="center">

# Tradie Smart

### AI-assisted operations infrastructure for field-service and trade businesses

**A modular Laravel platform combining business operations, offline field execution, deterministic dispatch, payments, and an operations-aware AI tool layer.**

[![Lint](https://github.com/Masterleeaus/Tradie-Smart/actions/workflows/lint.yml/badge.svg)](https://github.com/Masterleeaus/Tradie-Smart/actions/workflows/lint.yml)
[![Security](https://github.com/Masterleeaus/Tradie-Smart/actions/workflows/security.yml/badge.svg)](https://github.com/Masterleeaus/Tradie-Smart/actions/workflows/security.yml)
[![CI](https://github.com/Masterleeaus/Tradie-Smart/actions/workflows/ci.yml/badge.svg)](https://github.com/Masterleeaus/Tradie-Smart/actions/workflows/ci.yml)
[![Install Check](https://github.com/Masterleeaus/Tradie-Smart/actions/workflows/install-check.yml/badge.svg)](https://github.com/Masterleeaus/Tradie-Smart/actions/workflows/install-check.yml)

[Measured evidence](#measured-evidence) · [What is new](#what-is-new) · [Architecture](#architecture) · [Field runtime](#3-offline-first-field-runtime) · [Verification](#reproducible-verification) · [Limitations](#known-limitations) · [Quick start](#quick-start)

</div>

---

## Overview

**Tradie Smart** is a large modular operations platform for businesses that coordinate customers, jobs, workers, field activity, finance, compliance, and service delivery.

The repository combines an inherited Worksuite/Laravel business application with substantial field-service, mobile, payment, automation, and AI-oriented extension work.

The engineering focus is not a standalone chatbot. The interesting problem is how AI and automation can operate **inside a real business system** without replacing deterministic domain logic.

Tradie Smart currently includes:

- **Operations-aware AI tooling** — persisted conversations, tool registration, allowlisted dispatch, business-data tools, prompt/run records, and provider adapters.
- **Proposal-first AI integration** — AI-originated actions can be represented as pending proposals carrying confidence, risk, explanation, and evidence references before execution.
- **Offline field execution** — Titan Go queues field mutations locally and replays them when connectivity returns.
- **Deterministic dispatch** — worker availability, business hours, skills, and proximity are evaluated before assignment.
- **Field-service modules** — jobs, recurring work, routes, availability, skills, equipment, stock, timesheets, territories, service agreements, portals, and workflows.
- **Business operations** — CRM, bookings, accounting, purchasing, assets, inspections, quality control, reporting, compliance, suppliers, communications, and payments.

> **Core principle:** probabilistic intelligence should sit behind explicit business, tenant, tool, and execution boundaries rather than becoming the source of truth for operational state.


## Why this exists

Field-service software is a difficult environment for AI because the same system must coordinate customers, jobs, workers, schedules, payments, field evidence, compliance, and degraded connectivity.

Tradie Smart explores a narrower engineering question than “can an LLM run a business?”:

> **How can probabilistic AI participate in operational workflows without becoming the authority for operational truth?**

The repository approaches that problem with four complementary boundaries:

1. **Registered tools** instead of unrestricted model access to application internals.
2. **Pending proposals** instead of treating model output as completed action.
3. **Deterministic dispatch and skill rules** where ordinary software can make the decision reliably.
4. **Offline field queues with transactional replay** so core work continues when connectivity degrades.

The portfolio value is in those integration boundaries and failure modes, not in claiming original authorship over the inherited Worksuite codebase.

---


## Measured evidence

Tradie Smart currently has **partial, mixed verification rather than a clean production-readiness result**. That is important portfolio evidence in its own right.

The current GitHub Actions snapshot was checked on **5 October 2026** at commit `dd8a0910e3d62e3fab4b4407d5c9571043ec37dc`.

| Verification area | Documented result | What it establishes |
| --- | --- | --- |
| Lint workflow | ✅ Passing at current head | repository lint lane completed |
| Security workflow | ✅ Passing at current head | CodeQL/security workflow completed; some dependency-audit steps remain informational |
| Composer install | ✅ Passing before bootstrap failure | PHP dependency installation succeeds on PHP 8.3 |
| Frontend production build | ✅ Passing before bootstrap failure | production asset build completes |
| MySQL migrations | ✅ Passing before bootstrap failure | migrate and migrate:fresh complete |
| SQLite migrations | ✅ Passing before bootstrap failure | migrate and migrate:fresh complete |
| Route/bootstrap smoke | ❌ Failing at current head | unresolved `ProviderManagement\Entities\SubscribedService` reference |
| PHPUnit stage | ⏸ Blocked at current head | downstream tests are skipped because bootstrap fails first |
| Fresh install check | ❌ Failing at current head | fresh install reaches route bootstrap before failing |

The statuses above are tied to the stated commit. Future commits can change them; the workflow badges at the top of this README remain the quickest current signal.

This repository therefore demonstrates something more useful than a blanket “production-ready” claim: substantial install/build/migration progress, a clearly identified bootstrap failure, and an explicit boundary on what has and has not been verified.

---

## What is new

### 1. Operations-native AI tool boundary

Tradie Smart contains a real tool layer rather than only forwarding user text to a language model.

The Aitools module defines a registry of typed tools, tool metadata, schemas, business context, execution records, and a dispatcher that checks whether a tool is registered and enabled before calling it.

Key implementation:

- [ToolRegistry.php](Modules/Aitools/Tools/ToolRegistry.php)
- [AiToolDispatcher.php](Modules/Aitools/Services/AiToolDispatcher.php)
- [ChatOrchestrator.php](Modules/Aitools/Services/Chat/ChatOrchestrator.php)
- [AitoolsContext.php](Modules/Aitools/Tools/DTO/AitoolsContext.php)
- [AiToolsToolCall.php](Modules/Aitools/Entities/AiToolsToolCall.php)

Implemented business-facing tools include examples for:

- client search
- job search
- unpaid invoices
- today's operational summary
- business pulse
- task creation
- intent classification
- knowledge-base search
- summarisation and rewriting

~~~text
User request
    |
    v
Conversation context
    |
    v
Tool heuristic / explicit tool call
    |
    v
Registered tool boundary
    |
    +---- unavailable / disabled ----> controlled error
    |
    v
Business data result
    |
    v
Persisted tool trace
    |
    v
AI response
~~~

### Why it matters

A business AI assistant becomes much more useful when it can work with operational state, but direct unrestricted tool execution creates reliability and authority problems.

Tradie Smart separates:

1. natural-language interaction
2. tool selection
3. tool registration
4. domain execution
5. persistence and tracing

That boundary is more inspectable than a monolithic agent loop.

### Current boundary

This implementation is **not yet a complete autonomous-agent safety layer**.

The current chat orchestrator references a TitanZero service that is not present as a standalone module on the current main branch, while provider adapters also exist directly under Aitools and TitanCore. The architectural intent and the runtime wiring are therefore not yet fully reconciled.

That inconsistency is documented here instead of being hidden behind an "AI-ready" claim.

---

### 2. Proposal-first AI integration

The Booking module implements a useful safety pattern: AI-originated suggestions can be persisted as **pending proposals** rather than executed immediately.

[ProposalRunner.php](Modules/BookingModule/Services/Ai/ProposalRunner.php) records fields including:

- target module
- action type
- payload
- confidence
- risk level
- required confirmations
- explanation
- evidence references
- approval state
- execution state

The bridge is deliberately optional:

[ TitanZeroBridge.php ](Modules/BookingModule/Services/Ai/TitanZeroBridge.php)

If the expected AI gateway is unavailable, the integration degrades without blocking the booking domain.

~~~text
Operational context
      |
      v
AI bridge
      |
      +---- unavailable ----> no proposal / core flow continues
      |
      v
Suggested action
      |
      v
Proposal record
  confidence
  risk
  evidence
  status=pending
      |
      v
Approval / later execution boundary
~~~

This is an important distinction:

**model output is treated as a proposal, not automatically as business truth.**

The pattern is implemented in the Booking integration, but it should not yet be interpreted as a repository-wide enforcement guarantee.

---

### 3. Offline-first field runtime

The **Titan Go** module provides the worker-facing execution surface.

Its client-side sync service stores field mutations locally when offline, then submits the pending queue when connectivity returns.

Relevant implementation:

- [Titan Go integration notes](Modules/TitanGo/INTEGRATION.md)
- [sync.service.ts](Modules/TitanGo/Resources/js/src/services/sync.service.ts)
- [SyncController.php](Modules/TitanGo/Http/Controllers/Api/SyncController.php)
- [Titan Go API routes](Modules/TitanGo/Routes/api.php)

The current client queue:

- retains up to **200** local mutations
- retries failed items up to **3** times
- restores interrupted "processing" items as pending
- removes only confirmed successful items
- leaves network-failed items queued for another attempt

The server replays each item inside its own database transaction and returns per-item success or error state.

Supported replay paths include:

- notes
- worker status
- issues
- GPS location pings
- route points
- check-in
- check-out
- completion
- checklist steps

~~~text
Field worker
    |
    v
Action performed
    |
    +---- online ----------> API
    |
    +---- offline
           |
           v
       local queue
           |
      connection returns
           |
           v
       batch replay
           |
     per-item transaction
           |
      +---- success --> remove locally
      |
      +---- failure --> retry / retain
~~~

This gives the field application a real degraded-connectivity model rather than assuming permanent network access.

### Current limitation

The replay API accepts the client's local item ID for result mapping, but the current server implementation does not persist that ID as an idempotency key. An ambiguous network failure after a successful write could therefore permit duplicate replay for some mutation types.

That is a clear next hardening target.

---

### 4. Deterministic dispatch before AI

**SynapseDispatch** provides an inspectable dispatch baseline.

[HeuristicPlannerService.php](Modules/SynapseDispatch/Services/HeuristicPlannerService.php) ranks available workers using a deterministic score:

| Dimension | Current weighting |
| --- | ---: |
| Skill match | 0–50 |
| Proximity | 0–50 |
| Total | 0–100 |

Before ranking, workers can be filtered by:

- active state
- team
- requested job window
- business hours
- overlapping planned/dispatched jobs

Proximity uses Haversine distance, while [WorkerAvailabilityService.php](Modules/SynapseDispatch/Services/WorkerAvailabilityService.php) checks for schedule collisions.

A separate [SkillMatchService.php](Modules/FSMSkill/Services/SkillMatchService.php) evaluates:

- required skills
- worker skills
- certification expiry
- soon-to-expire warnings
- required skill levels

This deterministic baseline matters because it gives future ML or AI dispatch recommendations something concrete to beat, test, explain, and fall back to.

---

## Verified capabilities

| Capability | Implementation evidence |
| --- | --- |
| AI tool registry and tool execution | [Modules/Aitools](Modules/Aitools) |
| Persisted AI conversations and tool calls | [ChatOrchestrator.php](Modules/Aitools/Services/Chat/ChatOrchestrator.php) |
| Tenant/model policy middleware | [CheckAiPolicy.php](Modules/TitanCore/Http/Middleware/CheckAiPolicy.php) |
| AI usage cap middleware | [EnforceAICap.php](Modules/TitanCore/Http/Middleware/EnforceAICap.php) |
| AI proposal persistence | [ProposalRunner.php](Modules/BookingModule/Services/Ai/ProposalRunner.php) |
| Field worker PWA/runtime | [Modules/TitanGo](Modules/TitanGo) |
| Offline mutation replay | [SyncController.php](Modules/TitanGo/Http/Controllers/Api/SyncController.php) |
| Skill-aware worker matching | [SkillMatchService.php](Modules/FSMSkill/Services/SkillMatchService.php) |
| Availability/proximity dispatch | [Modules/SynapseDispatch](Modules/SynapseDispatch) |
| Payment operations | [Modules/ZeroPay](Modules/ZeroPay) |
| Accounting and reconciliation | [Modules/Accountings](Modules/Accountings) |
| Quality and inspection workflows | [Modules/QualityControl](Modules/QualityControl), [Modules/Inspection](Modules/Inspection) |
| Module-level health checks | Multiple FSM/Titan modules plus root test coverage |
| Filament administration surface | [docs/titan-filament.md](docs/titan-filament.md) |

---

## Architecture

Tradie Smart uses the Laravel host as the shared application and tenancy boundary, then adds bounded domain modules around it.

~~~text
                         +----------------------+
                         |   Owner / Manager    |
                         |  Laravel / Filament  |
                         +----------+-----------+
                                    |
                                    v
+----------------+       +----------+-----------+       +------------------+
|   Titan Go     | ----> |   Laravel host      | <---- | Customer / APIs  |
| field runtime  |       | auth / tenancy      |       | integrations     |
+-------+--------+       +----------+-----------+       +------------------+
        |                           |
        |                           v
        |              +------------+-------------+
        |              |     Domain modules       |
        |              | FSM / CRM / finance / QC |
        |              +------------+-------------+
        |                           |
        |           +---------------+----------------+
        |           |                                |
        v           v                                v
 offline sync   deterministic                  AI/tool layer
 + GPS          dispatch                       + proposals
 + checklist    + skills                       + providers
 + evidence     + availability                 + traces
        |           |                                |
        +-----------+---------------+----------------+
                                    |
                                    v
                           controlled state change
~~~

### Architectural boundaries

| Layer | Responsibility |
| --- | --- |
| Laravel host | authentication, shared models, tenancy, routes, common business services |
| Domain modules | bounded operational capabilities, providers, migrations, routes, views and tests |
| Titan Go | worker-facing field execution and degraded-connectivity operation |
| Dispatch / skill layer | deterministic assignment constraints and ranking |
| Aitools / TitanCore | AI providers, tools, prompts, logs, policies and assistant integration |
| Proposal layer | represents AI suggestions before later approval/execution |
| Finance / ZeroPay | payment session and reconciliation operations while retaining native finance truth |

---

## Example: field assignment path

A job assignment can combine multiple deterministic controls before any future AI optimisation is required.

~~~text
Job
 |
 +--> requested date/time
 +--> required duration
 +--> team
 +--> required skills
 +--> service location
 |
 v
Active workers
 |
 v
Business-hours filter
 |
 v
Overlap / availability filter
 |
 v
Skill evaluation
 |
 v
Skill + proximity scoring
 |
 v
Ranked worker candidates
~~~

This is intentionally understandable from source code. A future learned ranking model can be compared against the deterministic baseline rather than silently replacing it.

---

## Reliability, safety and authority

Tradie Smart currently contains several useful control mechanisms:

### Allowlisted tools

The AI dispatcher requires a tool to exist in the tool registry, be enabled, and resolve to a registered handler.

### Tenant model policy

TitanCore includes middleware that can reject a requested model if it is not in the configured tenant allowlist.

### Proposal state

Booking AI proposals are written with a pending state rather than being treated as completed actions.

### Logging

Conversation messages and tool calls can be persisted with execution status and duration.

### Graceful optional integration

Several AI bridges are written so an unavailable optional AI layer does not block core operational workflows.

### Important limitation

[EnforceAICap.php](Modules/TitanCore/Http/Middleware/EnforceAICap.php) is currently a best-effort control:

- database errors are fail-open
- request count acts as a proxy for token consumption
- the middleware is not yet a precise token/cost governor

This should be hardened before describing the AI runtime as strongly governed.

---

## Observability

Important AI and operational behaviour is represented by persisted records rather than disappearing inside a single opaque agent loop.

Current observability surfaces include:

- AI conversations and messages
- AI tool-call records
- prompt and run records
- proposal records with confidence, risk, explanation and evidence references
- AI usage records
- field-sync per-item results
- operational logs and Laravel application logs

Representative implementation paths include:

- [AiToolsToolCall.php](Modules/Aitools/Entities/AiToolsToolCall.php)
- [ChatOrchestrator.php](Modules/Aitools/Services/Chat/ChatOrchestrator.php)
- [ProposalRunner.php](Modules/BookingModule/Services/Ai/ProposalRunner.php)
- [TitanAIRunLogService.php](Modules/TitanCore/Services/TitanAIRunLogService.php)
- [UsageCostLogger.php](Modules/TitanCore/Services/UsageCostLogger.php)

The repository does not yet provide a single unified trace spanning model decision → tool call → proposal → approval → field mutation. That remains a useful observability target.

---

## Reproducible verification

## Current main-branch snapshot

Snapshot reviewed: **2026-10-05**  
Commit: **dd8a0910e3d62e3fab4b4407d5c9571043ec37dc**

The latest GitHub Actions evidence at that head is mixed and is reported as-is.

| Check | Current result | What was verified |
| --- | --- | --- |
| Lint | ✅ Passing | repository lint workflow completed successfully |
| Security workflow | ✅ Passing | workflow completed, including CodeQL; dependency-audit steps are informational |
| Composer install | ✅ Reached successfully in CI | dependencies install on PHP 8.3 |
| Frontend production build | ✅ Reached successfully in CI | npm install and production asset build complete |
| MySQL migrations | ✅ Reached successfully in CI | migrate and migrate:fresh complete |
| SQLite migrations | ✅ Reached successfully in CI | migrate and migrate:fresh complete |
| Route/bootstrap smoke check | ❌ Failing | unresolved ProviderManagement class reference |
| PHPUnit stage | ⏸ Blocked | skipped because bootstrap smoke check fails first |
| Fresh Install Check | ❌ Failing | fails at route-list bootstrap after successful install/migrations |

Current blocker:

~~~text
Target class [Modules\ProviderManagement\Entities\SubscribedService] does not exist.
~~~

The failure occurs during route/bootstrap validation, after dependency installation, frontend build, package discovery, and migrations have completed.

This is more useful evidence than claiming the repository is production-ready when its current main branch is not.

### CI definitions

- [.github/workflows/ci.yml](.github/workflows/ci.yml)
- [.github/workflows/install-check.yml](.github/workflows/install-check.yml)
- [.github/workflows/lint.yml](.github/workflows/lint.yml)
- [.github/workflows/security.yml](.github/workflows/security.yml)
- [.github/workflows/titancore-tests.yml](.github/workflows/titancore-tests.yml)

### Formal AI evaluation status

Tradie Smart does **not yet publish a dedicated AI-system benchmark** for tool selection, proposal quality, tenant isolation, or dispatch-policy comparison.

That is an important distinction:

- CI currently verifies install/build/migration/bootstrap behaviour.
- PHPUnit coverage exists across the host and selected modules.
- The repository contains inspectable AI and automation mechanisms.
- It does **not** yet provide a reproducible feature-enabled versus feature-disabled AI evaluation with a measured baseline.

A professional next step is therefore an `eval/` suite covering:

1. correct versus incorrect tool selection,
2. disabled/unregistered tool rejection,
3. proposal risk and confirmation behaviour,
4. tenant isolation,
5. dispatch baseline comparisons,
6. offline replay duplicate-resistance and recovery.

Until that exists, this README treats the AI evidence as **implementation evidence plus CI/test evidence**, not as a benchmark claim.

---

## Testing

The PHPUnit configuration covers application and module suites across:

- root Unit tests
- root Feature tests
- root Integration tests
- TitanCore tests
- Accountings tests
- Purchase tests

Additional module-local tests exist for areas including:

- Booking
- Complaint
- Inspection
- Managed Premises
- Quality Control
- Report
- TitanDocs

The repository also includes explicit regression checks for:

- Titan tenant isolation
- company provisioning
- custom module installation
- FSM module health/readiness
- native lead integration
- geofencing
- accounting/GST
- purchasing lifecycle

Because current main fails during route bootstrap, this README does **not** publish a passing-test count.

---

## Quick start

## Requirements

- PHP 8.3+
- Composer
- Node.js + npm
- MySQL or another Laravel-supported database
- required PHP extensions from the CI workflow

## Install

~~~bash
git clone https://github.com/Masterleeaus/Tradie-Smart.git
cd Tradie-Smart

composer install --no-interaction --prefer-dist
cp .env.example .env
php artisan key:generate

npm ci
npm run production

php artisan migrate
~~~

Detailed repository guidance:

[docs/install.md](docs/install.md)

### Current bootstrap warning

At the current main commit, fresh CI reaches the route bootstrap check and fails on the missing ProviderManagement SubscribedService class described in [Reproducible verification](#reproducible-verification).

Treat the repository as **active engineering work**, not as a clean production release until that blocker is resolved and the downstream test stage runs.

---

## Repository map

~~~text
Tradie-Smart/
|
+-- app/                         Laravel host application
+-- Modules/
|   +-- Aitools/                 AI tools, chat, KB, providers, logs
|   +-- TitanCore/               AI/platform services and policy middleware
|   +-- TitanGo/                 offline-capable field worker runtime
|   +-- SynapseDispatch/         deterministic dispatch engine
|   +-- FSMSkill/                worker skill/certification matching
|   +-- FSMCore/                 core field-service domain
|   +-- FSMRoute/                route operations
|   +-- FSMAvailability/         availability rules
|   +-- FSMRecurring/            recurring work
|   +-- FSMStock/                stock operations
|   +-- FSMVehicle/              vehicle operations
|   +-- FSMWorkflow/             workflow support
|   +-- BookingModule/           booking + AI proposal bridge
|   +-- Accountings/             accounting and reconciliation
|   +-- ZeroPay/                 payment operations
|   +-- QualityControl/          QC workflows
|   +-- Inspection/              inspection workflows
|   +-- StaffCompliance/         workforce compliance
|   +-- TitanIntegrations/       integration layer
|   +-- TitanVault/              vault-related platform capability
|   +-- ...
|
+-- routes/                      shared Laravel route surfaces
+-- database/                    host migrations, seeders and factories
+-- tests/                       application tests
+-- docs/                        install and production-readiness notes
+-- TitanDocs/                   architecture and system design material
+-- .github/workflows/           CI, lint, install, security and automation
~~~

---

# Selected Domain Estate

Tradie Smart is deliberately broader than a scheduling application.

### Customer and commercial

CRM, leads, contacts, bookings, proposals, estimates, contracts, customer portal, recurring service and service agreements.

### Field operations

Jobs, routes, availability, skills, vehicles, equipment, stock, timesheets, territories, field evidence, GPS, issues and checklists.

### Workforce and compliance

Employees, attendance, shifts, leave, documents, staff compliance, biometric/geofence support and skill/certification records.

### Quality

Inspection, quality control, complaint integration, corrective actions and service evidence.

### Finance

Invoices, payments, accounting, purchasing, suppliers, bank reconciliation, ZeroPay sessions and payment reconciliation assistance.

### Platform

Filament administration, modular installation, health checks, integrations, reporting, AI tooling, prompts, providers and observability records.

---

# Model and Provider Surface

Provider adapter code exists for:

| Provider / layer | Code present | End-to-end verified by current CI |
| --- | :---: | :---: |
| OpenAI adapter paths | ✅ | Not established |
| Anthropic adapter paths | ✅ | Not established |
| Aitools runtime | ✅ | Not established |
| TitanCore AI services | ✅ | Partial test surface exists |
| TitanZero gateway references | ⚠️ | Gateway wiring incomplete on current main |

Provider support should therefore be read as **implementation surface**, not a published compatibility benchmark.

---

# Reliability Engineering

The repository contains several explicit failure-handling patterns:

- per-item database transactions for offline replay
- retry-limited field sync
- worker schedule collision detection
- business-hours checks
- skill and certification validation
- module health checks
- fresh-install CI
- MySQL and SQLite migration validation
- tool availability checks
- optional AI bridges that avoid breaking core flows
- proposal state prior to later action
- tenant-aware records across many newer modules

Areas that still need hardening are listed below rather than being hidden.

---

# Known Limitations

## Current route bootstrap blocker

Main currently fails route listing because ProviderManagement references a missing:

~~~text
Modules\ProviderManagement\Entities\SubscribedService
~~~

This blocks the main PHPUnit stage in CI.

## TitanZero naming/runtime inconsistency

Multiple modules reference a TitanZero gateway/service, but the current repository module estate exposes TitanCore rather than a standalone TitanZero module. Direct provider adapters also remain in Aitools/TitanCore.

The intended "single AI gateway" architecture is therefore not yet consistently enforced in runtime code.

## AI cap is best-effort

The existing cap middleware fails open on storage errors and counts requests rather than verified token usage.

## Offline sync is not fully idempotent

Client queue IDs are returned for result mapping but are not currently persisted as server-side idempotency keys across every replay mutation.

## Security workflow caveat

The Security workflow passes, but Composer and npm audit commands are configured as informational with continue-on-error. A green workflow does not mean the dependency graph has zero advisories.

## Test completeness

There is a broad test estate, but current main does not reach the primary test stage because the route bootstrap check fails first.

## Upstream code provenance

The repository contains inherited Worksuite/vendor source. Repository ownership must not be confused with original authorship of every file or inherited feature.

## Repository governance files

The repository currently does **not** contain root-level `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md`, or `CHANGELOG.md` files.

`composer.json` declares the package license as MIT, but a professional public portfolio repository should still include an explicit root license file and contribution/security guidance before being treated as fully packaged open-source software.

---

# Engineering Priorities

1. **Repair ProviderManagement bootstrap** so fresh install and CI can progress into PHPUnit.
2. **Reconcile TitanZero/TitanCore AI boundaries** and eliminate contradictory gateway paths.
3. **Add server-side idempotency keys to Titan Go replay** for mutation safety after ambiguous network failures.
4. **Harden AI quotas** with real token/cost accounting and explicit failure policy.
5. **Add AI-specific evaluation suites** for tool selection, tenant isolation, proposal safety and failure handling.
6. **Publish reproducible dispatch evaluation** comparing deterministic ranking against alternative policies.
7. **Separate inherited Worksuite capability from repository-local contributions more explicitly in documentation.**
8. **Add root governance files** — `LICENSE`, `SECURITY.md`, `CONTRIBUTING.md`, and `CHANGELOG.md`.

---

# Design Decisions

## Why deterministic dispatch instead of immediately using an LLM?

Dispatch is operationally sensitive and highly structured. Availability, schedule overlap, skills, certification, business hours and distance can be evaluated deterministically.

This gives the system an explainable baseline and a safe fallback.

## Why proposals before AI execution?

A model can suggest useful work without automatically gaining authority to mutate operational state. Proposal records preserve confidence, risk and evidence for later review.

## Why local field queues?

Trade and service work frequently occurs in buildings, basements, plant rooms and remote sites where connectivity is unreliable. Field software that assumes permanent connectivity fails at exactly the wrong time.

## Why modular domains?

Accounting, dispatch, quality, payments, field execution and AI evolve at different rates. Laravel modules provide practical boundaries while still sharing authentication, tenancy and core business records.

---

# Technology

| Area | Technology |
| --- | --- |
| Backend | PHP 8.3, Laravel 10 |
| Admin UI | Filament 3, Livewire 3 |
| Frontend | Laravel Mix/Webpack, Bootstrap |
| Field client | React/TypeScript assets inside Titan Go |
| API/auth | Laravel Sanctum and Laravel routes |
| Database | Laravel migrations; CI exercises MySQL and SQLite |
| AI surface | Aitools, TitanCore, OpenAI/Anthropic adapter paths |
| Realtime | Laravel Echo / Pusher dependencies |
| Testing | PHPUnit 10 |
| Static analysis / style | Larastan, Laravel Pint, repository lint workflow |
| CI | GitHub Actions |
| Security | CodeQL plus Composer/npm audit workflows |

---

# Provenance

Tradie Smart contains **Worksuite SaaS vendor source and other upstream material** alongside repository-local extensions and integration work.

This distinction matters for a professional portfolio.

The repository should be evaluated for:

- architecture and integration decisions
- new modules and domain extensions
- field-service implementation
- Titan Go
- dispatch and skill logic
- AI/tool integration patterns
- testing and CI work
- migration and production-readiness work

It should **not** be interpreted as a claim of original authorship over the inherited Worksuite application or every third-party component.

Preserve upstream attribution, notices, and applicable licensing terms when redistributing or commercialising any portion of the repository.

The GitHub repository currently has no top-level LICENSE file, so repository-wide licensing should not be inferred solely from Composer metadata.

---

# Security and Responsible Use

Tradie Smart combines business data, field-worker data, payments, integrations and AI-assisted actions. That makes authority boundaries more important than model capability.

Before production deployment, operators should validate at minimum:

- tenant isolation
- authentication and role permissions
- secret management
- provider credentials
- webhook verification
- payment-provider configuration
- audit logging
- data-retention requirements
- AI tool allowlists
- proposal approval rules
- rate limits and token/cost controls
- offline replay idempotency

The current repository should **not** be interpreted as evidence that autonomous execution is safe for financial, legal, employment, safety-critical or compliance-sensitive decisions without human review and domain-specific controls.

A root `SECURITY.md` is still missing and is listed as a repository-hardening priority.

---

# Project Status

| Area | Status |
| --- | --- |
| Core Laravel application | 🟡 Active |
| Field-service module estate | 🟡 Active |
| Titan Go field runtime | 🟡 Active |
| Deterministic dispatch | 🟡 Active |
| AI tool layer | 🟠 Experimental / integration work |
| AI proposal pattern | 🟠 Implemented in selected integrations |
| Lint workflow | 🟢 Passing |
| Security workflow | 🟢 Passing with audit caveats |
| Fresh install | 🔴 Blocked at route bootstrap |
| Main CI | 🔴 Blocked before PHPUnit |
| Production certification | ⚪ Not claimed |

---

# License Status

`composer.json` currently declares **MIT**, but the repository does not yet contain a root `LICENSE` file.

For portfolio review, treat the licensing metadata as incomplete until the root license and any upstream/vendor attribution requirements are documented explicitly.

---

# Documentation

Useful starting points:

- [Installation guide](docs/install.md)
- [Filament architecture](docs/titan-filament.md)
- [Filament production-readiness notes](docs/titan-filament-production-readiness.md)
- [Filament runtime validation](docs/titan-filament-runtime-validation.md)
- [Titan Go integration](Modules/TitanGo/INTEGRATION.md)
- [ZeroPay module notes](Modules/ZeroPay/README.md)
- [Titan AI architecture material](TitanDocs/docs/04-AI/titan-zero.md)
- [Decision envelope design](TitanDocs/docs/06-automation/decision-envelopes.md)

---

# Engineering Principles

### Evidence over labels

"AI", "autonomous", "production-ready", and "secure" are not treated as self-validating claims.

### Deterministic controls around probabilistic systems

Use ordinary software rules where ordinary software rules are better.

### AI should propose before it receives authority

Generating a plausible action is different from being allowed to execute it.

### Field systems must survive degraded connectivity

Offline behaviour is part of the architecture, not an afterthought.

### Operational state must remain inspectable

Important tool calls, proposals, sync results and domain decisions should leave evidence.

### Known failures belong in the documentation

A visible unresolved CI blocker is more credible than a polished README that pretends the repository is green.

---

# Author

**Jason Lee — [@Masterleeaus](https://github.com/Masterleeaus)**

Focus areas:

**AI systems · agent/tool architecture · field-service automation · reliability · operational software**

---

<div align="center">

### Tradie Smart

**Business intelligence is most useful when it can operate safely inside the business.**

*Built to connect AI, deterministic workflows, field execution and operational evidence rather than treating them as separate demos.*

</div>
