# FEATURE — Secure local PHP runtime with Nginx and PHP-FPM

**ID:** 012-nginx-php-fpm · **Status:** proposed · **Date:** 2026-10-25

**Branch:** `feature/012-nginx-php-fpm`.

**User validation for Spec:** Paulo Bezerra approved this revised Feature by stating “aprovar” on 2026-10-25.

## Business need

This repository is a reusable PHP application skeleton intended to be adopted as the starting point for real projects. Its maintainers and downstream developers need a secure, current PHP runtime in the local Docker environment, and clear runtime guidance they can carry into projects that choose Docker for production. The project declares PHP 8.4, and its Dockerfile currently uses the moving `php:8.4-apache` image tag. The exact PHP patch version in any already-built or running local image has not been verified.

On 2026-10-21, the PHP project released PHP 8.4.27 to fix two critical remote-code-execution vulnerabilities, CVE-2026-109618 and CVE-2026-109619. The PHP advisory says these issues affect all PHP installations serving web requests, including PHP-FPM and Apache with mod_php. Changing the web server or PHP execution mode alone therefore does not ensure the runtime is patched. The current production deployment is Apache shared hosting and is outside this Feature's scope.

## Desired outcome

Maintainers and downstream developers can run the skeleton locally in Docker with Nginx handling HTTP and PHP-FPM executing PHP, using a PHP 8.4 build that includes the security fixes for CVE-2026-109618 and CVE-2026-109619. The local application remains accessible through its documented host port, PHP-FPM has no published host port, and the existing MySQL service and data remain available. The skeleton also clearly guides adopters who choose Docker for production to use Nginx with PHP-FPM and a security-fixed PHP build, avoiding the vulnerable PHP interpreter setup this work addresses.

The runtime's security status is verifiable, so rebuilding or starting the local stack cannot silently keep using a PHP build vulnerable to the cited critical issues. PHP-FPM is a runtime architecture choice, not a mitigation for PHP engine vulnerabilities.

## Boundaries and context

- **Included:** Update only the local Docker PHP runtime to a security-fixed PHP 8.4 build and use Nginx for HTTP with PHP-FPM for PHP execution. Preserve the existing local MySQL service and persistent data. Include reusable guidance for skeleton adopters choosing a Docker-based production runtime: use Nginx, PHP-FPM, and a security-fixed PHP build.
- **Out of scope:** Docker production deployment; changes to the production shared-hosting PRD, Apache deployment, or release workflow; application feature changes; database engine or schema changes; and changes to existing data.
- **Product constraints:** Production currently runs on Apache shared hosting and must remain unchanged. PHP must include the PHP project's fixes for CVE-2026-109618 and CVE-2026-109619 (PHP 8.4.27 or a vendor build confirmed to contain those fixes). Do not publish PHP-FPM on a host port. Preserve MySQL and its existing data volume.

## Success signals

- Developers can start the local Docker environment and access the application through its documented HTTP host port.
- The PHP runtime reports a security-fixed 8.4 build (8.4.27 or a confirmed vendor-patched equivalent); it does not run a build vulnerable to CVE-2026-109618 or CVE-2026-109619.
- PHP requests are executed by PHP-FPM, and PHP-FPM is not reachable through a published host port.
- MySQL remains available with its existing data after the local runtime change; no data reset is needed.
- The production Apache shared-hosting deployment and release workflow continue unchanged.
- Documentation for downstream adopters makes clear that a future Docker-based production deployment should use Nginx with PHP-FPM and a security-fixed PHP build; this guidance does not change this repository's current production deployment.

## Product questions

- What PHP patch version is present in the currently built or running local image? The Dockerfile specifies the moving `php:8.4-apache` tag, but the exact image contents have not been inspected.
- No decision is being made to move this repository's production deployment to Docker. The reusable guidance applies when an adopter independently chooses Docker for production; any implementation of such a deployment is separate work.

## Security context

- PHP's [21 October 2026 advisory](https://www.php.net/archive/2026.php#2026-10-21-5) identifies CVE-2026-109618 and CVE-2026-109619, says all PHP web-serving configurations including FPM are affected, and lists PHP 8.4.27 as fixed.
- PHP's [8.4.27 release announcement](https://news-web.php.net/php.announce/512) describes it as a security release fixing both critical vulnerabilities.
- PHP's [23 October informational advisory](https://www.php.net/archive/2026.php) additionally recommends `session.upload_progress.enabled=0` as a precaution for an unlikely CVE-2026-109619 attack path until the next regular bugfix release. Whether this unusual configuration applies to this application has not been established; it does not replace updating to a fixed PHP build.
