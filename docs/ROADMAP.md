# Satsuma — Roadmap

WordPress management platform. Laravel + Inertia/React (main app) + Filament (internal ops panel only).

## Architecture

- **Main app (Inertia/React)** — everything product-facing: dashboard, sites CRUD,
  clients CRUD, scan triggers, organization management. All feature work happens here.
- **Filament panel (`/admin`) — internal ops only.** Backend actions that span the
  whole SaaS: all users across organizations, and later subscription/billing
  internals. Product CRUD does not live here.

## Status

### Shipped
- [x] Core domain: `Client`, `Site`, `SitePlugin`, `SiteTheme` + migrations/factories
- [x] `WordPressClientService` — REST client, `X-Agent-Token` auth, 10s timeout,
  marks site `offline` on failure
- [x] `SyncSiteStatusJob` (Horizon/Redis) — refreshes status + upserts plugin/theme
  inventory, removes stale rows
- [x] Sites + Clients CRUD in the main app (Form Requests, Wayfinder-bound forms,
  modal create flows, delete confirmations)
- [x] Dashboard: stats cards, sites table, scan triggers; site page with agent
  setup (token reveal/copy, plugin download)
- [x] Companion WP agent plugin: `wp-agent/satsuma-agent.php` —
  `GET /wp-json/agent/v1/system-info`, `hash_equals` token check
  (`wp satsuma token <token>` or `SATSUMA_AGENT_TOKEN` constant)
- [x] Organizations: membership roles (owner/admin/member), session-based current
  org, sidebar switcher, org settings (rename + member management), policies
  enforcing role ranks, personal workspace on registration
- [x] Filament panel: internal `UserResource` (all users + org counts)
- [x] Design system: shadcn theme (light/dark OKLCH), borderless surfaces,
  settings-mode sidebar, flash toasts, blurred modal backdrops
- [x] Vulnerability intelligence: Wordfence Intelligence production feed
  (provider-agnostic `VulnerabilityFeed` contract, Patchstack stub for a later
  swap), memory-safe streaming index, CVSS severity per component on the site
  page, daily scheduled refresh
- [x] Connection pings + scan lifecycle (queued/running/idle) with live UI
  updates via partial-reload polling; synchronous Test Connection with
  definitive result toasts
- [x] Notifications: in-app bell (sidebar) + email via Resend — site offline /
  back online, new critical/high CVEs (deduped per site); org-member targeting
- [x] Scheduled uptime pings (every 5 min) + daily RDAP whois (registrar,
  expiry, nameservers) on the site page
- [x] Performance tab: queued Google PageSpeed (mobile + desktop) with per-site
  locks, 24h freshness, score rings/metrics/opportunities, AI summaries via
  Berget (Laravel AI SDK, openai-compatible)
- [x] Credits system: org-level monthly pools, hard block at zero, top-up
  grants, `credits_unlimited` for our own org, transaction ledger with
  estimated external cost, sidebar balance indicator, `/usage` page,
  Filament spend analytics (stats + 30-day chart)
- [x] Static privacy/cookie policy pages served outside the app
  (`public/*.html`)

### Known gaps
- Scans are **manual only** — no scheduled/automatic scanning yet
- No uptime/status **history** (sites only store current status)
- Agent plugin not yet tested against a real WordPress site beyond the
  first site (botniska.wtmgdev.com — working)
- Member invitations require the invitee to already have an account

## Next steps (recommended order)

1. **Scheduled scans** — per-site scan frequency setting; Laravel scheduler fanning
   out `SyncSiteStatusJob` via `Bus::batch()` per organization; `withoutOverlapping()`.
2. **Client reports** — severity data is now available per component; reports list
   pending updates with severity, WP/PHP currency and site status.
3. **Local dev environment** — Redis (DBngin) + Horizon run; `composer run dev`
   starts server + horizon + vite + logs (Herd PHP first on PATH; Homebrew php
   unlinked).
4. **Billing** — Stripe Cashier, flat per-organization subscription tiers (not
   per-site metering); Filament gains subscription internals.
5. **Email invitations** — invite members by email (signed invite links).

## Later phases

### EmDash support (pinned investigation)
EmDash (Cloudflare's Astro-based CMS, MIT, open source) is a modern WordPress
successor with a built-in WP importer — supporting it broadens the platform
beyond WordPress and the migration path is a selling point.

- [ ] **Platform abstraction** — `platform` column on sites
  (`wordpress|emdash`); extract a `SiteConnector` contract from
  `WordPressClientService` (ping, inventory, versions) with per-platform
  implementations; make the vulnerability feed WP-only (EmDash sites skip it).
- [ ] **EmDash connector** — EmDash ships a REST API + per-site MCP server;
  investigate what inventory it exposes (EmDash version, collections, plugins)
  and whether a small companion integration (or its API directly) can report
  health like the WP agent does.
- [ ] **WP → EmDash migration wizard** — orchestrate EmDash's own importer
  from Satsuma: trigger a WXR export via the WP agent (or the EmDash Exporter
  plugin path with an application password), hand it to the target EmDash
  site's import endpoint, track progress + verification checklist. Position:
  "modernize your site" upsell for agency clients.

### Safe updates
- Agent gains update actions (`POST /wp-json/agent/v1/updates`).
- Pre/post visual comparison via Puppeteer screenshots (sidecar worker or
  `spatie/browsershot`); auto-rollback on failure.
- Before shipping write actions, adopt request signing + nonce replay protection
  for the agent API (reference: ManageWP worker signs every request with RSA over
  `action + nonce`, 12h validity, 24h replay blacklist — evidence in
  `../managewp-clone/rea-artifacts/worker/src/MWP/`).

### Server-level management
- SSH/WP-CLI execution, PHP version upgrades, Nginx cache purges.
- Requires per-server credentials (encrypted casts + policy-gated actions).

## Local development

- `php artisan serve` — main app (Inertia/React)
- `/admin` — Filament panel (seeded: `admin@satsuma.test` / `password`)
- `php artisan horizon` — queue workers (requires Redis; `QUEUE_CONNECTION=redis`)
- Tests: `php artisan test` (SQLite in-memory, sync queue — no Redis needed)
- MySQL 8 runs via Herd's bundled `mysqld` (database `satsuma`)

## REA evidence trail

Worker plugin analysis (ManageWP Worker v4.9.38, sha256 `60beac17…`, REA evidence
`ev_e27257bb…`): wire protocol, auth model, 1-click login, action surface. Extracted
source kept at `../managewp-clone/rea-artifacts/worker/` for reference. The Orion
dashboard itself is closed-source and login-gated; protocol knowledge is reconstructed
from the worker side only.
