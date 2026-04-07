=== Acma Security Shield ===
Contributors: acmatvjrus
Donate link: https://thuc.me
Tags: security, waf, firewall, 2fa, audit, malware-scan, geo-blocking
Requires at least: 5.6
Tested up to: 6.4
Stable tag: 3.0.18
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional WordPress security solution built with Clean Architecture. Protect your site with multi-layered defense.

== Description ==

**Acma Security Shield** is a comprehensive, enterprise-grade security solution for WordPress, meticulously engineered using **Clean Architecture** principles and PSR-4 standards. It provides a multi-layered defense strategy, combining real-time runtime protection with proactive monitoring and forensic auditing.

Unlike generic security plugins, Acma Security Shield focuses on high performance and maintainable code, ensuring your site remains secure without the overhead of legacy architectural bloat.

= Key Features =

*   **Runtime Protection & WAF**: Detects and blocks SQL Injection, XSS, and LFI/RFI attacks.
*   **Rate Limiting**: Intelligent IP-based rate limiting to prevent automated scraping.
*   **Geo-Blocking**: Block or allow access based on country codes (supports Cloudflare).
*   **Login Hardening**: Custom login URL, Login Lockout, and forced strong password policies.
*   **Two-Factor Authentication (2FA)**: Native TOTP support for administrator and other roles.
*   **File Integrity Monitor**: Alerts you to unauthorized changes in core files.
*   **Malware Scanner**: Scans for suspicious patterns in PHP, JS, and HTML files.
*   **Audit Logs**: Forensic logging of all administrative actions.
*   **Uploads Protection**: Prevents PHP execution in the uploads directory.

== Installation ==

1. Upload the `acma-security-shield` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your settings under the 'Acma Security' menu in the admin dashboard.

== Frequently Asked Questions ==

= Does this plugin work with Cloudflare? =
Yes! It supports `CF-IPCountry` and `X-Forwarded-For` headers out of the box for accurate Geo-Blocking and Rate Limiting.

= Is it compatible with WooCommerce? =
Absolutely. The plugin is designed to be compatible with major WordPress plugins including WooCommerce.

== Screenshots ==

1. The Security Dashboard showing the status of your site.
2. Configuring 2FA for your user account.
3. Viewing the Audit Logs for administrative actions.

== Changelog ==

= 3.0.18 =
* Initial release on WordPress.org.
* Renamed to Acma Security Shield to meet naming guidelines.
* Improved WAF rules and Rate Limiting logic.
