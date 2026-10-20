#!/usr/bin/env bash

set -uo pipefail

fail() {
    printf 'Shared-hosting deployment failed: %s\n' "$1" >&2
    exit 1
}

[[ $# == 6 && "$1" == '--release' && "$3" == '--package' && "$5" == '--target' ]] \
    || fail 'usage: tools/deploy-shared-hosting.sh --release TAG --package ARCHIVE --target DIRECTORY'
release_tag=$2
package_archive=$4
deployment_target=$6

script_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P) \
    || fail 'could not resolve the application directory.'
project_root=$(cd -- "$deployment_target" && pwd -P) \
    || fail 'could not resolve the deployment target directory.'
[[ -d "$project_root" ]] || fail 'deployment target must already exist.'
environment_file="$project_root/.env"

read_dotenv_path() {
    local wanted_name=$1
    local line name value found='' found_set=0

    [[ -e "$environment_file" ]] || return 2
    [[ -f "$environment_file" && -r "$environment_file" ]] \
        || fail 'configuration step: .env exists but cannot be read as a regular file.'

    while IFS= read -r line || [[ -n "$line" ]]; do
        line=${line%$'\r'}
        [[ "$line" =~ ^[[:space:]]*([A-Z_]+)[[:space:]]*=(.*)$ ]] || continue
        name=${BASH_REMATCH[1]}
        [[ "$name" == "$wanted_name" ]] || continue
        [[ "$found_set" == 0 ]] || fail "duplicate $wanted_name assignment in .env."

        value=${BASH_REMATCH[2]}
        value=${value#"${value%%[!$' \t']*}"}
        value=${value%"${value##*[!$' \t']}"}
        if [[ ${#value} -ge 2 ]]; then
            if [[ ${value:0:1} == "'" && ${value: -1} == "'" ]] \
                || [[ ${value:0:1} == '"' && ${value: -1} == '"' ]]; then
                value=${value:1:${#value}-2}
            fi
        fi
        found=$value
        found_set=1
    done < "$environment_file"

    [[ "$found_set" == 1 ]] || return 2
    printf '%s' "$found"
}

configured_value() {
    local variable_name=$1
    local read_status

    configured_value_result=''
    configured_value_is_set=0
    if [[ -v $variable_name ]]; then
        configured_value_result=${!variable_name}
        configured_value_is_set=1
        return
    fi
    if configured_value_result=$(read_dotenv_path "$variable_name"); then
        configured_value_is_set=1
        return
    else
        read_status=$?
    fi
    [[ "$read_status" == 2 ]] || fail "could not read $variable_name from .env."
}

validate_configured_path() {
    local variable_name=$1
    local value=$2
    local expected_kind=$3
    [[ -n "$value" ]] || fail "$variable_name is configured but empty."
    [[ "$value" == /* ]] || fail "$variable_name must be an absolute executable path."
    [[ "$value" != *'$'* && "$value" != *'`'* && "$value" != *';'* \
        && "$value" != *'|'* && "$value" != *'&'* && "$value" != *'<'* \
        && "$value" != *'>'* && "$value" != *$'\n'* ]] \
        || fail "$variable_name contains unsupported shell syntax."
    if [[ "$expected_kind" == 'file' ]]; then
        [[ -f "$value" && -r "$value" ]] || fail "$variable_name does not point to a readable file."
    else
        [[ -x "$value" && ! -d "$value" ]] || fail "$variable_name does not point to an executable file."
    fi
}

configured_value PHP_BIN
php_setting=$configured_value_result
php_setting_is_set=$configured_value_is_set
configured_value COMPOSER_BIN
composer_setting=$configured_value_result
composer_setting_is_set=$configured_value_is_set

if [[ "$php_setting_is_set" == 1 ]]; then
    validate_configured_path PHP_BIN "$php_setting" executable
    php_bin=$php_setting
else
    php_bin=''
    while IFS= read -r candidate; do
        [[ -n "$candidate" ]] || continue
        if "$candidate" -r 'exit(PHP_VERSION_ID >= 80400 && PHP_VERSION_ID < 90000 ? 0 : 1);' >/dev/null 2>&1; then
            php_bin=$candidate
            break
        fi
    done < <(type -a -p php 2>/dev/null || true)
    [[ -n "$php_bin" ]] || fail 'PHP step: no PHP compatible with ^8.4 was found on PATH; set PHP_BIN to its absolute executable path.'
fi
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80400 && PHP_VERSION_ID < 90000 ? 0 : 1);' >/dev/null 2>&1 \
    || fail 'PHP step: PHP_BIN must run a version compatible with ^8.4.'

if [[ "$composer_setting_is_set" == 1 ]]; then
    validate_configured_path COMPOSER_BIN "$composer_setting" file
    composer_bin=$composer_setting
else
    composer_bin="$project_root/composer.phar"
    [[ -f "$composer_bin" ]] || fail 'Composer step: the tracked composer.phar is missing; set COMPOSER_BIN to a host-local Composer PHAR.'
fi
composer_version=$("$php_bin" "$composer_bin" --version --no-ansi 2>/dev/null) \
    || fail 'Composer step: the selected Composer file cannot run with PHP_BIN.'
[[ "$composer_version" =~ ^Composer\ version\ 2\. ]] \
    || fail 'Composer step: Composer 2 is required and must run with PHP_BIN.'

command -v tar >/dev/null 2>&1 || fail 'package step: tar is required on the host.'
command -v rsync >/dev/null 2>&1 || fail 'package step: rsync is required on the host.'
[[ -f "$package_archive" && -r "$package_archive" ]] || fail 'package step: release archive is not readable.'

PHP_BIN="$php_bin" bash "$script_dir/verify-deploy-package.sh" "$package_archive" "$release_tag" "$(
    tar -xOzf "$package_archive" .deploy-release 2>/dev/null | tail -n 1
)" || fail 'package step: release archive validation failed; the application has not been changed.'

temporary_directory=$(mktemp -d "${TMPDIR:-/tmp}/sandbox-php-release.XXXXXX") \
    || fail 'package step: could not create a temporary directory.'
cleanup() {
    rm -rf -- "$temporary_directory"
}
trap cleanup EXIT

if ! tar -xzf "$package_archive" -C "$temporary_directory"; then
    fail 'package step: could not extract the validated release archive.'
fi

if ! rsync -a \
    --no-perms \
    --exclude='/.env' \
    --exclude='/vendor/' \
    --exclude='/public/.htaccess' \
    --exclude='/.deploy-release' \
    "$temporary_directory/" "$project_root/"; then
    fail 'release file transfer failed; the application may contain a partial update, so rerun after resolving the rsync error.'
fi

if ! chmod 750 "$project_root"; then
    fail 'permission step: could not allow the web server to traverse the application directory.'
fi
if ! chmod 755 "$project_root/public"; then
    fail 'permission step: could not make the public directory traversable by the web server.'
fi
if [[ -f "$project_root/public/.htaccess" ]] && ! chmod 644 "$project_root/public/.htaccess"; then
    fail 'permission step: could not make the host .htaccess readable by the web server.'
fi

if ! (
    cd -- "$project_root" \
        && COMPOSER_NO_INTERACTION=1 COMPOSER_ALLOW_SUPERUSER=0 \
            "$php_bin" "$composer_bin" install --no-dev --optimize-autoloader --no-interaction --no-progress
) >/dev/null 2>&1; then
    fail 'Composer install step failed; resolve the dependency or platform issue and rerun.'
fi

if ! (
    cd -- "$project_root" \
        && "$php_bin" "$project_root/vendor/bin/phinx" migrate -e production --no-interaction
) >/dev/null 2>&1; then
    fail 'Production migration step failed; check DB_USERNAME privileges and migration status, then rerun.'
fi

printf 'Shared-hosting deployment completed: %s installed, production dependencies installed, and pending migrations applied.\n' "$release_tag"
