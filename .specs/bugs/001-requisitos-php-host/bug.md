# BUG — Document PHP host requirements before project creation

**ID:** 001-requisitos-php-host · **Status:** open · **Date:** 2026-10-25

**Branch:** `fix/001-requisitos-php-host` (based on `origin/main`).

**User validation to start Code:** approved by Paulo Bezerra on 2026-10-25.

## Problem

The README presents the `composer create-project` command before stating the PHP host requirements. Users can run it without the required PHP version or extensions and Composer stops before creating the project. The affected users are adopters installing the skeleton with Composer on the host. Severity: low; the workaround is to install the missing requirements and rerun the command.

## Reproduction

Run `composer create-project --stability=dev paulobezerra/sandbox-php aTesouraria --remove-vcs` on a host whose Composer PHP lacks `pdo_mysql`. Observed: Composer rejects the package because `ext-pdo_mysql` is missing. Expected: the installation section states the required host PHP version and extensions before the command.

## Root cause

`README.md` places the create-project command in “Create a project” (currently lines 5–17), while the host requirements appear later under “Requirements” (currently line 27). The root `composer.json` requires PHP `^8.4`, `ext-pdo_mysql`, and `ext-pdo`; Composer checks these platform requirements while resolving the package. The README additionally lists `mbstring` for running the application on a host. This is a documentation ordering and completeness issue; no application behavior is implicated.

## Fix

Update the README installation section to state, before the command, that host Composer must use PHP `^8.4` with `PDO` and `pdo_mysql` enabled. Also state that `mbstring` is needed when running the app on the host, as already described under Requirements. Keep Docker-based setup and Composer package requirements unchanged; do not change application code, dependencies, or the HTTP/data contracts.

## Tasks

- **B001-01 — Document host PHP prerequisites before the Composer command.** **Acceptance:** given the README installation instructions, when a host user follows them, then the PHP version and required extensions checked by Composer (`PDO` and `pdo_mysql`) are stated before `create-project`, and the host runtime `mbstring` requirement remains clear. **Test:** manual documentation regression check; verify prerequisite ordering and compare the listed Composer requirements with `composer.json` (documentation-only change has no meaningful automated behavior test). **Gate:** inspect the README diff and confirm links, command examples, and requirements remain consistent with `composer.json` and the README Requirements section.
