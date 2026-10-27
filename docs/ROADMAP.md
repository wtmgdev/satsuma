# Shoal — Roadmap

Self-hosted WordPress management platform. Laravel + Inertia/React (main app) + Filament (internal ops panel only).

V1 scope is intentionally narrow: **client/website management + triggerable background site scans**.
No billing, no multi-tenancy, no vulnerability feeds yet.

## Architecture

- **Main app (Inertia/React)** — everything product-facing: dashboard, sites CRUD,
  clients CRUD, scan triggers. This is where all feature work happens.
- **Filament panel (`/admin`) — internal ops only.** Backend actions that span the
  whole SaaS: all users across teams/organizations, and later subscription/billing
  internals. Product CRUD does not live here.

## V1 (implemented)

- `Client`, `Site`, `SitePlugin`, `SiteTheme` models + migrations
- `WordPressClientService` — REST client, `X-Agent-Token` bearer auth, 10s timeout,
  marks site `offline` on failure
- `SyncSiteStatusJob` (Horizon/Redis) — refreshes site status + upserts plugin/theme
  inventory, removes stale rows
- Sites CRUD + Clients CRUD in the main app (Form Requests, Inertia `<Form>` +
  Wayfinder action bindings, delete confirmations via Dialog)
- Filament panel (`/admin`): internal `UserResource` only
- Inertia/React dashboard: stats cards, sites table, site detail, scan trigger
- Companion WP agent plugin: `wp-agent/shoal-agent.php` — registers
  `GET /wp-json/agent/v1/system-info`, token-checked via `hash_equals`
  (`wp shoal token <token>` or the `SHOAL_AGENT_TOKEN` constant)

## Revised incremental roadmap

### Phase 2 — Organizations (multi-tenancy) + billing
- [x] Organization model with membership roles (owner/admin/member); users get a
  personal workspace on registration.
- [x] Clients and sites scoped to the current organization (session-based switcher
  in the sidebar); policies enforce role ranks.
- [x] Organization settings page (rename + member management) in the sidebar
  settings mode; internal Filament panel shows org counts per user.
- [ ] Stripe billing. Flat per-organization subscription tiers instead of
  per-site per-addon metering. Cashier + webhooks.
- [ ] Email invitations for members (currently: add existing users by email).

### Phase 3 — Vulnerability intelligence + scheduled scans
- Patchstack (or WPScan) API integration; map installed plugin/theme slugs+versions
  against the vulnerability DB.
- Semver comparison job to flag outdated components (already have `update_available`
  from the agent; add CVE severity columns).
- Scheduled scans: Laravel scheduler fanning out `SyncSiteStatusJob` via
  `Bus::batch()` per organization; `withoutOverlapping()`.

### Phase 4 — Safe updates
- Agent gains update actions (`POST /wp-json/agent/v1/updates`).
- Pre/post visual comparison via Puppeteer screenshots (sidecar worker or
  `spatie/browsershot`); auto-rollback on failure.
- Reference: ManageWP's worker signs every dashboard request with RSA over
  `action + nonce` (12h validity, 24h replay blacklist) — adopt HMAC request signing +
  nonce replay protection for the agent API before shipping write actions.
  Evidence: `rea-artifacts/worker/src/MWP/EventListener/MasterRequest/AuthenticateRequest.php`,
  `src/MWP/Security/NonceManager.php` (ManageWP Worker v4.9.38, GPL).

### Phase 5 — Server-level management
- SSH/WP-CLI execution, PHP version upgrades, Nginx cache purges.
- Requires per-server credentials (encrypted casts + policy-gated actions).

## Local development

- `php artisan serve` — main app (Inertia/React)
- `/admin` — Filament panel (seeded: `admin@shoal.test` / `password`)
- `php artisan horizon` — queue workers (requires Redis; `QUEUE_CONNECTION=redis`)
- Tests: `php artisan test` (SQLite in-memory, sync queue — no Redis needed)
- MySQL 8 runs via Herd's bundled `mysqld` (database `shoal`)

## REA evidence trail

Worker plugin analysis (ManageWP Worker v4.9.38, sha256 `60beac17…`, REA evidence
`ev_e27257bb…`): wire protocol, auth model, 1-click login, action surface. Extracted
source kept at `../managewp-clone/rea-artifacts/worker/` for reference. The Orion
dashboard itself is closed-source and login-gated; protocol knowledge is reconstructed
from the worker side only.
