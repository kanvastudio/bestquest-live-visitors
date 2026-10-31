# Security and support

For a potential vulnerability, please do not post exploit details in a public issue. Contact the maintainer privately via https://bestquest.net/contact/ and include affected version, reproduction steps, and impact.

The public heartbeat endpoint is deliberately accessible to anonymous visitors. It applies basic validation, origin checks when the Origin header is provided, and rate limits. This is not a guarantee against forged requests or advanced automated traffic. Site operators should use WordPress, PHP, and hosting security updates and test with their specific caching configuration.

This package has undergone PHP and JavaScript syntax checks but not a formal third-party security audit or broad WordPress-version compatibility testing. Do not interpret a passing syntax check as WordPress.org approval.
