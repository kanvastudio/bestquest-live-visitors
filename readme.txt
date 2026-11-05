=== BestQuest Live Visitors ===
Tags: live visitors, visitor tracking, analytics, real time, visitor history
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.5.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A self-hosted WordPress dashboard showing approximate live visitor activity, article-link clicks, referrers, and recent sessions.

== Description ==

BestQuest Live Visitors provides a lightweight WordPress admin dashboard showing:

* Approximate live visitors and visitors active in the last 15 minutes.
* Approximate distinct browser identifiers observed today.
* Current/last page, while page-journey data remains stored internally for session tracking.
* Tracked article-link clicks, including link text, destination URL, source page, and click time.
* Action column for live and recent visitor sessions.
* Referrer origin, browser/device family, and browser-reported timezone.
* Recent sessions after their last heartbeat falls outside the live window.
* Configurable heartbeat interval, live timeout, data retention, frontend tracking, and IP storage.
* Exclusion of administrator traffic and optional exclusion by a locally hashed IP address.
* Optional automatic removal of plugin tables/settings when the plugin is deleted.

No external analytics service or API key is required. The optional public BestQuest footer credit is disabled by default and is shown only after an administrator explicitly enables it in the plugin settings. By default, click tracking applies to links inside article content (`.entry-content`), links using the BestQuest affiliate class `bq-affiliate-text-link`, links inside `bq-affiliate-cta`, links with `rel="sponsored"`, links using `bq-track-click`, and links marked with `data-bqlv-track="click"`. Header, menu, footer, and WordPress admin navigation are not tracked unless explicitly marked.

The tracker stores data in the site's own WordPress database. Session duration is elapsed first-to-last recorded heartbeat, not verified time spent reading. An inactive session is not proof that a user disconnected. Cookie blocking, ad blockers, browser privacy features, stale full-page caches, and bots can affect counts.

Version 1.5.4 hardens the anonymous tracking endpoints with a WordPress frontend nonce, same-site Origin/Referer validation, per-session throttles, higher shared-IP ceilings, a new-session creation cap, and a 500-event per-session ceiling. These measures reduce forged traffic and database-bloat risk but do not make anonymous analytics impossible to spoof.

Developed by BestQuest: https://bestquest.net/

== Installation ==

1. Upload the `bestquest-live-visitors` folder to `/wp-content/plugins/`, or install the plugin ZIP using Plugins > Add New > Upload Plugin.
2. Activate BestQuest Live Visitors.
3. Open Live Visitors in the WordPress admin dashboard.
4. Optionally exclude your current IP and adjust heartbeat, live window, retention, tracking, IP storage, and uninstall cleanup settings.
5. If you use full-page caching, verify the frontend script and REST heartbeat work on cached pages. The frontend nonce is time-limited, so unusually long cache lifetimes should be tested.

== Frequently Asked Questions ==

= Is a visitor count the same as the number of people? =

No. Unique visitors are approximate distinct browser identifiers. Shared browsers, privacy settings, deleted local storage, and multiple devices affect accuracy.

= Does this plugin send analytics data to BestQuest? =

No. Visitor activity is submitted to your own WordPress REST endpoint and stored in your site's database. The external links in documentation and plugin metadata are informational.

= Does it store raw IP addresses? =

IP storage is enabled by default because the administrator dashboard can use it to distinguish visits from probes and abusive traffic. Administrators can disable IP storage under Live Visitors > Settings. Turning it off immediately clears IP addresses already stored by this plugin, while hashed visitor/session identifiers and activity history remain until normal retention cleanup. Ignored-IP entries are stored as salted one-way hashes.

= Can I integrate consent or privacy logic? =

Yes. Frontend tracking can be disabled in Settings. Developers can also use the `bqlv_tracking_enabled` and `bqlv_should_track_request` filters to gate tracking, `bqlv_store_ip_address` to control IP retention, and `bqlv_client_ip` to supply an address from a trusted proxy/CDN integration. Do not trust forwarding headers unless the proxy itself is trusted.

The plugin uses browser localStorage and sessionStorage identifiers. Your site's legal obligations depend on your jurisdiction and configuration; assess consent and update your privacy notice before enabling behavioral analytics.

= What happens on deactivation or removal? =

Deactivation stops tracking and the scheduled cleanup task but preserves data for reactivation. When the plugin is deleted, its tables and settings are removed by default. Administrators can disable that deletion behavior in Settings if they intentionally want to preserve the data for a later reinstall.

= How long is data kept? =

The default retention is 7 days; the dashboard allows changing this between 1 and 30 days. Cleanup is performed by WordPress cron, which depends on site traffic or an external cron setup.

= How are the public REST endpoints protected? =

Anonymous visitors must be able to send heartbeats and click events, so the endpoints are public by design. Version 1.5.4 requires a frontend nonce and same-site request evidence, applies per-session and per-IP throttles, limits new-session creation per IP, and caps stored events per session. These are abuse-reduction measures, not authentication of anonymous visitors.

== Changelog ==

= 1.5.4 =
* Restored the optional BestQuest public footer credit as an explicit opt-in setting; it is disabled by default.
* Hardened heartbeat and action endpoints with a frontend WordPress nonce and same-site Origin/Referer validation.
* Added per-session rate limits and raised the shared-IP ceiling to reduce false 429 responses for visitors behind NAT.
* Added a separate cap of 60 new sessions per IP per minute to reduce forged-session database bloat.
* Added a hard ceiling of 500 stored events per session.
* Added a `bqlv_client_ip` filter for carefully configured trusted proxy/CDN integrations; forwarding headers are not trusted automatically.
* Added Settings controls to enable/disable frontend tracking and raw IP storage.
* Turning IP storage off now clears previously stored plugin IP addresses immediately.
* Added `bqlv_tracking_enabled`, `bqlv_should_track_request`, and `bqlv_store_ip_address` privacy/consent integration filters.
* Added `uninstall.php`; plugin tables and settings are deleted on plugin deletion by default, with an administrator setting to preserve them if desired.
* Security review credit: BlueSA — Thembelani Mthethwa.

= 1.5.3 =
* Action click-history dropdowns now remain open across the automatic Live visitors refresh.
* Closing a dropdown keeps it closed on the next refresh.
* Live refresh interval and tracking behavior are unchanged.
* No database schema change; existing data is preserved.

= 1.5.2 =
* Removed the visible Session path column from Live visitors and Recent visitors.
* Page-journey events continue to be recorded internally; no tracking data is deleted.
* Gives the Action column more room and reduces dashboard clutter.
* No database schema change; existing data is preserved.

= 1.5.1 =
* Track ordinary links inside article content, not only affiliate/sponsored links.
* Keep header, menu, footer, and WordPress admin navigation out of Action unless explicitly marked.
* Ignore non-HTTP link schemes for click-event storage.
* No database schema change; existing v1.5.0 data is preserved.

= 1.5.0 =
* Added affiliate/sponsored link click tracking.
* Added an Action column to live and recent visitor tables.
* Click events record link text, destination URL, source page, and click time in the local events table.
* Added explicit `bq-track-click` and `data-bqlv-track="click"` hooks for tracking additional links without tracking all site navigation.
* Existing page/session history is preserved during the database upgrade.

= 1.4.0 =
* Added administrator-only IP address display for live and recent sessions.
* Recent-history view now shows First seen + Recorded span instead of redundant Last seen.
* Front-end tracker no longer loads on WordPress 404 pages, reducing scanner noise.
* Existing session rows from earlier versions cannot recover past IP addresses and will show blank IPs.

= 1.3.4 =
* Packaging and documentation release: GPL license text, WordPress.org readme, privacy policy guidance, and security notes.
* No intentional visitor-tracking behavior changes from 1.3.2.

= 1.3.2 =
* Added BestQuest attribution metadata and source comments.

= 1.3.1 =
* Improved iOS Chrome, Firefox, Edge, and Opera identification.

= 1.3.0 =
* Added recent visitor session history view with show/hide control.

= 1.2.1 =
* Refined session expiration and visitor/session consistency checks.

= 1.2.0 =
* Reduced referrer retention and added basic bot and traffic limiting safeguards.

= 1.1.0 =
* Improved visitor counting and dashboard summary counters.

== Upgrade Notice ==

= 1.5.4 =
Hardens anonymous tracking endpoints, adds privacy controls and uninstall cleanup. Existing visitor/session data and settings are preserved. IP storage remains enabled by default unless the administrator turns it off.

= 1.5.3 =
Keeps expanded Action histories open while the live dashboard refreshes. No database migration is required.
