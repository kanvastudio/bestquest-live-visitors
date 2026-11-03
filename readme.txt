=== BestQuest Live Visitors ===
Tags: live visitors, visitor tracking, analytics, real time, visitor history
Requires at least: 6.5
Requires PHP: 8.0
Stable tag: 1.5.3
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
* Configurable heartbeat interval, live timeout, and data retention.
* Exclusion of administrator traffic and optional exclusion by a locally hashed IP address.

No external analytics service or API key is required. By default, click tracking applies to links inside article content (`.entry-content`), links using the BestQuest affiliate class `bq-affiliate-text-link`, links inside `bq-affiliate-cta`, links with `rel="sponsored"`, links using `bq-track-click`, and links marked with `data-bqlv-track="click"`. Header, menu, footer, and WordPress admin navigation are not tracked unless explicitly marked. The tracker stores data in the site's own WordPress database. Session duration is elapsed first-to-last recorded heartbeat, not verified time spent reading. An inactive session is not proof that a user disconnected. Cookie blocking, ad blockers, browser privacy features, and bots can affect counts.

Developed by BestQuest: https://bestquest.net/

IMPORTANT — GitHub distribution: The plugin adds a small public footer attribution, “Visitor insights powered by BestQuest”, linking to https://bestquest.net/ automatically on installation. The website owner can disable it at any time in Live Visitors > Settings. No requests to BestQuest are made by this credit. This default-on public link makes the GitHub edition unsuitable for submission to the official WordPress.org Plugin Directory without modification.

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

Yes. Since version 1.4.0, each session can retain the request IP address locally for the configured retention period (7 days by default) so administrators can investigate probes and abusive traffic. Ignored-IP entries remain salted one-way hashes.

= Does it require consent? =

This plugin uses browser localStorage and sessionStorage identifiers and processes visitor activity. Your site's legal obligations depend on your jurisdiction and configuration; assess consent and update your privacy notice before enabling it on visitors' browsers. The plugin does not provide a consent banner or a consent-management integration.

= What happens on deactivation or removal? =

Deactivation stops tracking and the scheduled cleanup task. By default it leaves historical visitor tables and settings in place for possible reactivation. See the bundled PRIVACY.md for the data lifecycle and manual deletion guidance.

= How long is data kept? =

The default retention is 7 days; the dashboard allows changing this between 1 and 30 days. Cleanup is performed by WordPress cron, which depends on site traffic or an external cron setup.

== Changelog ==

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

= 1.5.3 =
Keeps expanded Action histories open while the live dashboard refreshes. No database migration is required.

= 1.5.2 =
Removes the visible Session path column while preserving page-journey data internally. No database migration is required.

= 1.5.1 =
Expands Action tracking to ordinary article-content links. No database migration is required and existing visitor/session/click data is preserved.

= 1.5.0 =
Adds affiliate/sponsored link click tracking and an Action column. The existing events table is upgraded in place; current settings and historical page/session data are preserved.

= 1.4.0 =
Adds short-retention IP visibility, simplifies recent-session timing columns, and suppresses tracking on WordPress 404 pages. Existing settings and session history are preserved; pre-upgrade rows cannot recover past IP addresses.
