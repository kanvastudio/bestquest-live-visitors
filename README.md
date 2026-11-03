# BestQuest Live Visitors

A self-hosted WordPress live visitor dashboard with recent session history, short-retention IP visibility, and affiliate-link click tracking for administrators.

**Website:** https://bestquest.net/  
**License:** GPL-2.0-or-later (see `LICENSE`)  
**Current package:** 1.5.3

## Public BestQuest credit (GitHub edition)

**On installation, the plugin displays a small footer credit on public pages:** “Visitor insights powered by BestQuest” linking to https://bestquest.net/. This is **enabled by default**, and site administrators can switch it off under **Live Visitors → Settings → Show public footer credit linking to BestQuest**. It never sends a tracking request to BestQuest. The link uses `rel="nofollow"` and is not intended as an SEO endorsement. This default-on link is not suitable for WordPress.org plugin directory submission; a separate version with default-off credit would be required.

## Features

- Approximate live and recently active visitor counts
- Last visited page, referrer origin, browser/device, timezone
- Page-journey data retained internally for session tracking without occupying a permanent dashboard column
- Article-link click tracking with link text, destination, source page, and click time
- Dashboard Action column for live and recent sessions
- IP-hash-based exclusions for site owners
- Adjustable retention (default seven days)

For detailed installation, FAQs, change history and directory metadata, see [`readme.txt`](readme.txt). For data processing and limitations, see [`PRIVACY.md`](PRIVACY.md); for vulnerability reporting, see [`SECURITY.md`](SECURITY.md).

## Install

Use WordPress **Plugins → Add New Plugin → Upload Plugin**, select the installable ZIP, activate it, and open **Live Visitors**. Test with the site's cache enabled.

## Limitations

The results are estimates: browser identifiers are not confirmed individuals, and inactivity does not prove a disconnect. The plugin uses browser storage and does not provide consent gating. Assess your site's privacy obligations before deployment.

## License and attribution

Copyright (c) 2026 BestQuest. Licensed under the GNU General Public License, version 2 or (at your option) any later version.

Project and publisher: https://bestquest.net/
