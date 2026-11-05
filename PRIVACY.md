# Privacy notes — BestQuest Live Visitors v1.5.4

BestQuest Live Visitors operates locally on the WordPress site where it is installed. It does not intentionally transmit analytics records to BestQuest or an external analytics provider.

## Data processed

- Random browser identifier in `localStorage` (`bqlv_vid`) and per-tab session identifier in `sessionStorage` (`bqlv_sid`). They are sent to the site's REST endpoint and stored as salted HMAC hashes in the database.
- Current/last path, page title, referring origin, browser/device family, browser-reported timezone, heartbeat timestamps, and tracked article-link click details.
- The request IP can be stored in the local session table when **Store visitor IP addresses** is enabled (default). Turning that setting off immediately clears IP addresses already stored by this plugin.
- Ignored-IP entries remain salted one-way hashes.

The request IP can still be used transiently for rate limiting even when raw IP storage is disabled; transient throttle keys contain keyed hashes rather than the raw IP.

## Consent and integration controls

Frontend tracking can be disabled globally in Live Visitors > Settings. Developers can also use:

- `bqlv_tracking_enabled` — global server-side tracking gate.
- `bqlv_should_track_request` — per-request gate suitable for consent-cookie logic.
- `bqlv_store_ip_address` — controls whether new session rows retain the raw IP.
- `bqlv_client_ip` — allows a site owner to provide an address from a trusted proxy/CDN integration. Forwarding headers must not be trusted without verifying the proxy.

The plugin uses localStorage and sessionStorage, so website operators remain responsible for consent and disclosure requirements in their jurisdictions. Full-page caching must also be considered when implementing per-visitor consent logic.

## Storage and retention

Data is stored in local WordPress database tables `{$prefix}bqlv_sessions` and `{$prefix}bqlv_events`; page-view and click events share the events table. Settings are stored in WordPress options. Session records are retained 7 days by default (configurable from 1 to 30 days), with cleanup performed by WordPress cron.

The browser identifier in localStorage has no automatic expiry; clearing site data removes it. The session identifier is retained in sessionStorage until the tab's session ends or the plugin rotates it after inactivity.

## Deactivation and uninstall

Deactivation stops tracking and unschedules cleanup while preserving data for reactivation.

On plugin deletion, `uninstall.php` removes the two plugin tables and plugin-owned options by default. Administrators can disable **Delete BestQuest Live Visitors tables and settings when the plugin is deleted** if they intentionally want to preserve data for a later reinstall.

## Security-related processing

Version 1.5.4 uses a frontend WordPress nonce, same-site Origin/Referer validation, per-session and per-IP throttling, a new-session cap, and a per-session event ceiling. These controls reduce automated abuse but do not authenticate anonymous visitors as real humans.
