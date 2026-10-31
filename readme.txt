=== BestQuest Live Visitors ===
Tags: live visitors, visitor tracking, analytics, real time, visitor history
Requires at least: 6.5
Requires PHP: 8.0
Stable tag: 1.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A self-hosted WordPress dashboard showing approximate live visitor activity, session paths, referrers, and recent sessions.

== Description ==

BestQuest Live Visitors provides a lightweight WordPress admin dashboard showing:

* Approximate live visitors and visitors active in the last 15 minutes.
* Approximate distinct browser identifiers observed today.
* Current page and recorded session path.
* Referrer origin, browser/device family, and browser-reported timezone.
* Recent sessions after their last heartbeat falls outside the live window.
* Configurable heartbeat interval, live timeout, and data retention.
* Exclusion of administrator traffic and optional exclusion by a locally hashed IP address.

No external analytics service or API key is required. The tracker stores data in the site's own WordPress database. Session duration is elapsed first-to-last recorded heartbeat, not verified time spent reading. An inactive session is not proof that a user disconnected. Cookie blocking, ad blockers, browser privacy features, and bots can affect counts.

Developed by BestQuest: https://bestquest.net/

== Installation ==

1. Upload the `bestquest-live-visitors` folder to `/wp-content/plugins/`, or install the plugin ZIP using Plugins > Add New > Upload Plugin.
2. Activate BestQuest Live Visitors.
3. Open Live Visitors in the WordPress admin dashboard.
4. Optionally exclude your current IP and adjust heartbeat, live window, and retention settings.
5. If you use full-page caching, verify the frontend script and REST heartbeat work on cached pages.

== Frequently Asked Questions ==

= Is a visitor count the same as the number of people? =

No. Unique visitors are approximate distinct browser identifiers. Shared browsers, privacy settings, deleted local storage, and multiple devices affect accuracy.

= Does this plugin send analytics data to BestQuest? =

No. Visitor activity is submitted to your own WordPress REST endpoint and stored in your site's database. The external links in documentation and plugin metadata are informational.

= Does it store raw IP addresses? =

No raw IP addresses are saved in the plugin's visitor tables. The request IP is used transiently for traffic limits and optional local exclusion; exclusion entries are salted one-way hashes.

= Does it require consent? =

This plugin uses browser localStorage and sessionStorage identifiers and processes visitor activity. Your site's legal obligations depend on your jurisdiction and configuration; assess consent and update your privacy notice before enabling it on visitors' browsers. The plugin does not provide a consent banner or a consent-management integration.

= What happens on deactivation or removal? =

Deactivation stops tracking and the scheduled cleanup task. By default it leaves historical visitor tables and settings in place for possible reactivation. See the bundled PRIVACY.md for the data lifecycle and manual deletion guidance.

= How long is data kept? =

The default retention is 7 days; the dashboard allows changing this between 1 and 30 days. Cleanup is performed by WordPress cron, which depends on site traffic or an external cron setup.

== Changelog ==

= 1.3.3 =
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

= 1.3.3 =
Adds full licensing and WordPress.org-style documentation; existing session data and settings are preserved.
