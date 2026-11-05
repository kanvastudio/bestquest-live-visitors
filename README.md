# BestQuest Live Visitors

A self-hosted WordPress live visitor dashboard with recent session history, optional short-retention IP visibility, and article-link click tracking for administrators.

Current package: **1.5.4**

Website: https://bestquest.net/

License: GPL-2.0-or-later

## v1.5.4 security and privacy hardening

- Frontend WordPress nonce required for heartbeat/action requests.
- Same-site Origin/Referer validation.
- Per-session rate limits plus a higher shared-IP ceiling to reduce NAT false positives.
- New-session creation capped per IP.
- Maximum 500 stored events per session.
- Trusted proxy/CDN IPs can be supplied only through the explicit `bqlv_client_ip` filter; forwarding headers are never trusted automatically.
- Frontend tracking and raw-IP storage can be disabled in Settings.
- Disabling IP storage immediately clears IP addresses already stored by the plugin.
- `bqlv_tracking_enabled`, `bqlv_should_track_request`, and `bqlv_store_ip_address` filters are available for privacy/consent integrations.
- `uninstall.php` removes plugin tables/settings on deletion by default; administrators can opt to preserve them.

Security review credit: **BlueSA — Thembelani Mthethwa**.

## Important behavior

Anonymous analytics cannot be cryptographically tied to a real human visitor. The protections above reduce automated forgery and database-bloat risk but are not a guarantee against distributed or browser-capable automation.

IP storage is enabled by default to preserve the administrator IP column. It can be disabled at any time. Abuse throttling may still use the request IP transiently without writing that IP into the visitor table.

See `PRIVACY.md` and `SECURITY.md` for details.

## Optional footer credit

BestQuest Live Visitors includes an optional public footer credit: “Visitor insights powered by BestQuest”. It is **disabled by default** and appears only after an administrator explicitly enables **Show public footer credit linking to BestQuest** under Live Visitors > Settings. It can be turned off again at any time.
