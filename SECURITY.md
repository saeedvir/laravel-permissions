# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.0.x   | :white_check_mark: |
| 1.0.x   | :x:                |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

Instead, please report them via email to: **saeed.es91@gmail.com**

You should receive a response within 48 hours. If for some reason you do not, please follow up via email to ensure we received your original message.

Please include the following information:

-   Type of issue (e.g. buffer overflow, SQL injection, cross-site scripting, etc.)
-   Full paths of source file(s) related to the manifestation of the issue
-   The location of the affected source code (tag/branch/commit or direct URL)
-   Any special configuration required to reproduce the issue
-   Step-by-step instructions to reproduce the issue
-   Proof-of-concept or exploit code (if possible)
-   Impact of the issue, including how an attacker might exploit it

## Security Best Practices

When using this package:

1. **Never expose raw permissions** in your API responses
2. **Use HTTPS** in production
3. **Enable cache encryption** if storing sensitive data in cache
4. **Use database transactions** (enabled by default)
5. **Validate user input** before permission checks
6. **Use guards** to separate different user types
7. **Set proper expiration times** for expirable permissions
8. **Use Redis with password** if caching sensitive data
9. **Regularly update** to the latest version
10. **Monitor permission changes** in production

## Known Security Considerations

### Cache Poisoning

-   Use Redis with password authentication
-   Enable cache tags for better isolation
-   Set proper cache permissions

### SQL Injection

-   Package uses Eloquent/Query Builder (protected)
-   Always validate input before using in permission slugs

### Permission Bypass

-   Super admin role bypasses all checks (by design)
-   Ensure super admin role is properly protected
-   Audit super admin assignments regularly

### Session Security

-   Use Laravel's session security features
-   Enable CSRF protection
-   Set secure session cookies

## Disclosure Policy

-   Security issues are handled privately
-   Fix is developed and tested
-   New version is released
-   Security advisory is published
-   Credits are given to reporter (if desired)

## Comments on this Policy

If you have suggestions on how this process could be improved, please submit a pull request.

---

Last updated: November 2024
