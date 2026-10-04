![Tradie-Smart — Laravel operations suite](docs/images/portfolio-banner.svg)

<div align="center">

# Tradie-Smart

**A modular Laravel operations suite for coordinating customers, projects, field service, workforce, and finance workflows.**

</div>

Tradie-Smart brings the day-to-day operating surface of a service business into one Laravel application: customer and lead records, projects and tasks, estimates and invoices, employee operations, attendance, contracts, support, reporting, and a broad field-service module family. Its strongest engineering story is the extension boundary—domain modules own their routes, providers, migrations, views, and tests while the host application supplies shared authentication, tenancy, and platform services.

## What it solves

Service businesses outgrow disconnected spreadsheets and inbox workflows when work moves from enquiry to project, scheduled visit, timesheet, invoice, and payment. Tradie-Smart gives those transitions a shared application model and a Laravel-native module system that can be extended without replacing the host application.

## Product architecture

| Capability | Source evidence | Engineering value |
| --- | --- | --- |
| Customer and commercial workflow | [`routes/web.php`](routes/web.php) wires clients, client contacts and documents, leads, proposals, estimates, projects, contracts, products, invoices, credit notes, payments, and finance reports behind the authenticated account surface. | Keeps customer, work, and money workflows connected instead of duplicating records between tools. |
| Workforce operations | The same route surface includes employees, departments, designations, attendance, leave, shifts, weekly timesheets, approvals, and employee documents. | Gives managers a shared place to plan work and account for the people delivering it. |
| Field-service extensions | [`Modules/`](Modules/) contains current FSM modules including `FSMCore`, `FSMProject`, `FSMRoute`, `FSMRouteAvailability`, `FSMSales`, `FSMVehicle`, `FSMStock`, `FSMRepair`, and `FSMWorkflow`, alongside `TitanCore` and `ZeroPay`. | Makes specialised operational capabilities installable and reviewable as bounded Laravel modules. |
| Authenticated API | [`routes/api.php`](routes/api.php) exposes authenticated endpoints for leads, estimates, tasks, invoices, payments, and contracts. | Supports integrations without bypassing the application's authentication boundary. |
| Frontend build | The root [`package.json`](package.json) uses Laravel Mix/Webpack with Bootstrap, Quill, charts, Echo, Pusher, and the application asset libraries. | Keeps the server-rendered Laravel application paired with a repeatable asset pipeline. |
| Verification surface | [`phpunit.xml`](phpunit.xml) includes application and module Unit/Feature suites, including `TitanCore`, `Accountings`, and `Purchase` module tests. | Makes module behavior part of the same test contract as the host application. |

## A typical workflow

```text
Lead or customer
      │
      ▼
Estimate / proposal
      │
      ▼
Project + tasks + members
      │
      ├─ schedule, attendance, timelog, field-service module
      ▼
Invoice / payment / reporting
```

The application is intentionally broad: shared account routes handle common business records, while `Modules/` supplies field-service and vertical extensions. That structure is useful for teams that need a coherent core but cannot force every operational domain into one monolithic controller layer.

## Quickstart

### Prerequisites

- PHP 8.3 or newer
- Composer
- Node.js and npm
- A database configured for the Laravel environment

### Install

```bash
composer install --no-interaction --prefer-dist
copy .env.example .env
php artisan key:generate
php artisan migrate
npm ci
npm run development
```

For a production asset build, use `npm run production`. The repository also contains the more detailed [`docs/install.md`](docs/install.md) guide.

### Test

```bash
vendor/bin/phpunit
vendor/bin/pint --test
```

Run environment-sensitive integrations only after configuring local credentials and services. Never commit `.env` or customer data.

## Repository map

| Path | Role |
| --- | --- |
| [`app/`](app/) | Core Laravel application, controllers, models, policies, services, and shared infrastructure. |
| [`Modules/`](Modules/) | Domain modules with providers, routes, migrations, resources, and module-specific tests. |
| [`routes/`](routes/) | Web, API, panel, webhook, and authentication route composition. |
| [`database/`](database/) | Migrations, seeders, and factories for the application and modules. |
| [`tests/`](tests/) | Application-level verification suites. |
| [`docs/install.md`](docs/install.md) | Repository-specific installation and smoke-check guidance. |

## Provenance and attribution

Tradie-Smart contains Worksuite SaaS vendor source and other upstream application material alongside repository-local module and integration work. Preserve the upstream vendor attribution, license, and notice files. This README describes the engineering surface without claiming original authorship for upstream portions; distinguish local changes from inherited code when presenting or redistributing the repository.

## Evidence and scope

This README is aligned to the canonical `Tradie-Smart` repository at main commit `9acebc1` and the source paths reviewed on 2026-10-04. The feature map above is based on checked-in routes, modules, package manifests, and PHPUnit configuration; it is not a claim that every optional module or external integration has been freshly deployed.

## License

Review the repository's retained license, vendor notices, and third-party terms before redistribution or commercial reuse.