# Satsuma

WordPress management platform for agencies and teams — every site's versions,
updates, security and performance in one calm dashboard.

Laravel 13 + Inertia 3/React 19 main app, Filament 5 reserved for internal
operations, a companion WordPress agent plugin, and queued background jobs on
Horizon/Redis.

## What it does

- **Organizations & teams** — sites and clients are scoped to an organization;
  owner/admin/member roles; personal workspace created on registration
- **Site inventory scans** — the [agent plugin](wp-agent/satsuma-agent.php)
  reports WordPress/PHP versions, plugins and themes (premium updaters
  included) over an authenticated REST endpoint
- **Update tracking with severity** — installed versions are matched against
  the Wordfence Intelligence vulnerability feed (provider-agnostic; Patchstack
  stub included) and surfaced as CVSS-scored critical/high/medium/low
- **Monitoring & notifications** — scheduled pings every 5 minutes; site
  offline/back-online and new critical CVEs notify the whole organization
  in-app and by email (Resend)
- **Performance tab** — queued Google PageSpeed audits (mobile + desktop) with
  score rings, core metrics, top opportunities, and AI summaries (Berget via
  the Laravel AI SDK)
- **Domain oversight** — daily RDAP lookups: registrar, registration/expiry
  dates, nameservers
- **Credits** — org-level monthly credit pools meter AI and analysis usage,
  with a transaction ledger, sidebar balance, `/usage` page and Filament spend
  analytics
- **Internal ops (`/admin`)** — Filament panel for cross-org user management,
  credit grants and spend tracking. No product CRUD lives here.

## Requirements

- PHP 8.3+ (Redis, GD, SQLite extensions), Composer
- Node.js 18+
- MySQL 8 (or SQLite for a quick start)
- Redis for Horizon (optional — jobs degrade gracefully without it)

## Getting started

```bash
composer setup        # install, .env, key, migrate, npm install + build
npm run dev           # vite
php artisan dev       # or: composer run dev — server + horizon + vite + logs
```

Seed test accounts and demo data:

```bash
php artisan db:seed
# admin@satsuma.test / password — superadmin (/admin), unlimited org
# demo@satsuma.test  / password — customer; agency org with sites,
#                               plugins, an analysis and a credit ledger
```

### Environment

Key variables (see `.env.example`):

```ini
QUEUE_CONNECTION=redis          # sync works without Redis
VULN_FEED_PROVIDER=wordfence    # or patchstack (stub)
WORDFENCE_API_TOKEN=            # free key from wordfence.com
GOOGLE_PAGESPEED_API_KEY=
BERGET_API_KEY=                 # openai-compatible; BERGET_MODEL=zai-org/GLM-5.3-Flash
FIRECRAWL_API_KEY=
RESEND_API_KEY=                 # falls back to the log mailer without it
METERED_ACTIONS_DRY_RUN=        # true = record credit usage, never enforce (local dev)
```

## Connecting a site

1. Download the agent plugin from the dashboard or any site page
   (`satsuma-agent.zip`, always built from source)
2. Install it on the WordPress site (Plugins → Add New → Upload)
3. Paste the site's token into Settings → Satsuma Agent in wp-admin
4. Test Connection, then Run Scan

## Development

```bash
php artisan test          # Pest suite (SQLite in-memory, sync queue)
npm run types:check       # TypeScript
npm run build             # production build
php artisan wayfinder:generate --with-form   # regenerate typed routes/actions
```

Scheduler entries (uptime pings, whois, vulnerability feed refresh, credit
resets) run under `schedule:work` or a cron hitting `schedule:run`.

## Architecture notes

- **Product-facing work happens in the Inertia/React app** — controllers under
  `app/Http/Controllers`, pages under `resources/js/pages`, typed routes via
  [Wayfinder](https://github.com/laravel/wayfinder) (regenerate after route
  changes, always with `--with-form`)
- **Filament is internal-only** — cross-org administration and spend analytics
- **Background jobs** (`app/Jobs`) run on Horizon; dispatches go through
  `App\Actions\DispatchSiteJob`, which degrades gracefully when Redis is down
- **Vulnerability feed** — `app/Services/VulnerabilityFeed/` holds the
  contract + implementations; the large feed index lives in the file cache
  store, refreshed at most once per day with upstream rate-limit backoff
- **Credits** — `app/Services/CreditService.php` charges atomically
  (`lockForUpdate`), records every action in `credit_transactions` with an
  estimated external cost; `credits_unlimited` organizations are recorded but
  never blocked

## Roadmap

See [docs/ROADMAP.md](docs/ROADMAP.md) — shipped items, known gaps, and the
pinned EmDash (multi-platform + WP→EmDash migration) investigation.
