# Privacy and data handling

BestQuest Live Visitors v1.3.3 operates locally on the WordPress site where it is installed. It does not intentionally transmit analytics records to BestQuest or any external analytics provider.

## Collected data

- Random browser identifier in `localStorage` (`bqlv_vid`) and per-tab session identifier in `sessionStorage` (`bqlv_sid`). They are sent to the site's REST endpoint and stored as salted HMAC hashes in the database.
- Page paths without query strings, page titles, referring website origin (not the full external path), approximate browser and device types, browser-reported timezone, and first/last recorded activity timestamps.
- The visitor's network IP is inspected transiently to apply a request rate limit and, if the administrator chooses, ignore traffic from that IP. Ignored IP values are stored as one-way keyed hashes. Raw IPs are not stored in the plugin's visitor tables; hosting and web-server logs are separate.

## Storage and retention

Data is stored in local WordPress database tables `{$prefix}bqlv_sessions` and `{$prefix}bqlv_events`; settings are stored in WordPress options. Session records are retained 7 days by default (configurable from 1 to 30 days), with cleanup performed by WordPress cron. The browser identifier in localStorage has no automatic expiry; clearing site data removes it. The session identifier is retained in sessionStorage until the tab's session ends or the plugin rotates it after inactivity.

## Visitor rights and consent

The plugin currently does **not** implement consent gating, a consent-management interface, or integration with WordPress personal-data export/erasure tools. In jurisdictions requiring consent for nonessential browser identifiers or behavioral analytics, website operators should obtain consent before loading the tracker. Consider disabling the plugin until suitable consent gating exists. Do not represent these hashes as fully anonymous under every applicable privacy law.

## Uninstallation

Deactivation cancels the cleanup schedule, but leaves database tables and settings intact to preserve configuration if the plugin is reactivated. Site administrators who need permanent removal must delete the two tables and `bqlv_*` options through appropriate database administration after taking a backup. This is intentionally **not** done automatically.

## Scope

The browser and timezone are inferred or reported by the visitor's software and can be incorrect; bot filtering is heuristic. `Last seen` reflects the last successful heartbeat and cannot confirm a network disconnect. Recorded session span is not engaged time.

Information about the developer: https://bestquest.net/
