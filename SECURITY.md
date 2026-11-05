# Security policy

Please report suspected vulnerabilities privately to the project maintainer before public disclosure.

## Tracking endpoint model

The heartbeat and action REST endpoints must remain accessible to anonymous website visitors. Version 1.5.4 therefore uses layered abuse controls rather than authentication:

- a WordPress-generated frontend nonce;
- same-site Origin validation with a same-host Referer fallback;
- per-session heartbeat/action limits;
- a deliberately higher per-IP ceiling to reduce denial of service for users behind NAT;
- a separate new-session creation cap per IP;
- a 500-event maximum per session;
- validation and sanitization before database writes.

These measures reduce forged statistics and database bloat but cannot make anonymous analytics impossible to spoof. A browser-capable or distributed attacker may still generate traffic within the limits.

## Proxy handling

Only `REMOTE_ADDR` is trusted by default. The plugin does not automatically trust `X-Forwarded-For` or similar headers. Administrators using a verified reverse proxy/CDN can supply a trusted client address through the `bqlv_client_ip` filter.

## Privacy controls

Raw IP storage can be disabled; doing so clears IP addresses already stored by the plugin. Tracking can also be disabled or gated through the provided privacy/consent filters.

Security review credit for the v1.5.4 hardening: **BlueSA — Thembelani Mthethwa**.
