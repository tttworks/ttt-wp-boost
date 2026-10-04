<p align="center">
  <img src="assets/logo.png" alt="TTTWorks" width="260">
</p>

# TTT WP Boost

**Lightweight page cache and asset optimization for Elementor-powered WordPress sites.**

Built and maintained in production — this plugin runs on a fleet of real client sites, not in a lab.

---

## What makes it different

**Three-tier object cache fallback: Redis → Memcached → WP Transients.**
Not every host gives you Redis. This one still delivers value on plain shared hosting —
and upgrades itself automatically the moment Redis becomes available.

**Page cache that never serves a stale admin view.**
Static HTML goes to logged-out visitors only. Logged-in admins always see live pages.

**Performance work measured on sites we run ourselves** — not synthetic benchmarks.
Typical Elementor sites see meaningful PageSpeed improvements; results vary by site and hosting.

---

## What it does

| Module | Effect |
|---|---|
| **Page Cache** | Static HTML served from disk for anonymous visitors — cuts TTFB and PHP load |
| **Object Cache** | Three-tier fallback (Redis / Memcached / Transients) — caching never hard-fails |
| **Asset Minification** | Consolidates and minifies CSS/JS output |
| **Dashboard Accelerator** | Disables the block editor where unwanted, slows Heartbeat, removes unused admin assets, caps post revisions |

---

## Who it's for

Agencies, hosting providers and WordPress engineers running **multiple Elementor sites on
mixed infrastructure** — where "just install Redis" isn't an option for every client.

---

## Engineering notes

This plugin went through independent code review and a follow-up fix audit. The reports are
included in this repository rather than summarised:

| Document | Purpose |
|---|---|
| [`CODE-AUDIT-REPORT.md`](CODE-AUDIT-REPORT.md) | Initial full-code audit |
| [`POST-FIX-AUDIT-REPORT.md`](POST-FIX-AUDIT-REPORT.md) | Verification audit after fixes |
| [`PLUGIN-REVIEW.md`](PLUGIN-REVIEW.md) | Feature and code review |
| [`DEVELOPMENT-REPORT.md`](DEVELOPMENT-REPORT.md) | Build log |
| [`ROADMAP.md`](ROADMAP.md) | What's next |

---

## License

Apache License 2.0 — permissive, **commercial use permitted**, trademark rights not granted. See [LICENSE](LICENSE).

---

**[TTTWorks](https://tttworks.com)** — Production WordPress engineering.
