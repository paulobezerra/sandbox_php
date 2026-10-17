#!/usr/bin/env bash

set -uo pipefail

fail() {
    printf 'Shared-hosting deployment failed: %s\n' "$1" >&2
    exit 1
}

script_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P) \
    || fail 'could not resolve the application directory.'
project_root=$(cd -- "$script_dir/.." && pwd -P) \
    || fail 'could not resolve the application directory.'
environment_file="$project_root/.env"

# Read only the three literal path assignments used by this installer. Never
# evaluate the file as shell code. The documented values contain no expansion.
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
        [[ -f "$value" && -r "$value" ]] \
            || fail "$variable_name does not point to a readable file."
    else
        [[ -x "$value" && ! -d "$value" ]] \
            || fail "$variable_name does not point to an executable file."
    fi
}

configured_value PHP_BIN
php_setting=$configured_value_result
php_setting_is_set=$configured_value_is_set
configured_value GIT_BIN
git_setting=$configured_value_result
git_setting_is_set=$configured_value_is_set
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

if ! "$php_bin" -r 'exit(PHP_VERSION_ID >= 80400 && PHP_VERSION_ID < 90000 ? 0 : 1);' >/dev/null 2>&1; then
    fail 'PHP step: PHP_BIN must run a version compatible with ^8.4.'
fi

if [[ "$git_setting_is_set" == 1 ]]; then
    validate_configured_path GIT_BIN "$git_setting" executable
    git_bin=$git_setting
else
    git_bin=$(command -v git) || fail 'Git step: git was not found on PATH.'
fi

if [[ "$composer_setting_is_set" == 1 ]]; then
    validate_configured_path COMPOSER_BIN "$composer_setting" file
    composer_bin=$composer_setting
else
    composer_bin="$project_root/composer.phar"
    [[ -f "$composer_bin" ]] || fail 'Composer step: the tracked composer.phar is missing.'
fi

[[ -f "$composer_bin" ]] || fail 'Composer step: the selected Composer file does not exist.'
composer_version=$("$php_bin" "$composer_bin" --version --no-ansi 2>/dev/null) \
    || fail 'Composer step: the selected Composer file cannot run with PHP_BIN.'
[[ "$composer_version" =~ ^Composer\ version\ 2\. ]] \
    || fail 'Composer step: Composer 2 is required and must run with PHP_BIN.'

git_run() {
    GIT_TERMINAL_PROMPT=0 GIT_ASKPASS=/bin/false SSH_ASKPASS=/bin/false \
        GIT_SSH_COMMAND='ssh -oBatchMode=yes' "$git_bin" -C "$project_root" "$@"
}

branch=$(git_run branch --show-current 2>/dev/null) \
    || fail 'Git step: could not inspect the current branch.'
[[ "$branch" == 'main' ]] || fail 'Git step: the checkout must be on local main.'

if [[ -n "$(git_run status --porcelain --untracked-files=normal 2>/dev/null)" ]]; then
    fail 'Git step: the working tree must be clean.'
fi

upstream=$(git_run rev-parse --abbrev-ref --symbolic-full-name '@{upstream}' 2>/dev/null) \
    || fail 'Git step: local main must track a remote main branch.'
[[ "$upstream" == */main ]] || fail 'Git step: local main must track a remote main branch.'
remote=${upstream%/main}
[[ -n "$remote" ]] || fail 'Git step: local main must track a remote main branch.'
git_run remote get-url "$remote" >/dev/null 2>&1 \
    || fail 'Git step: the configured upstream remote is unavailable.'

if ! git_run -c core.askPass=/bin/false pull --ff-only "$remote" main >/dev/null 2>&1; then
    fail 'Git fast-forward pull failed; check remote access and that local main can fast-forward.'
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

printf 'Shared-hosting deployment completed: remote main updated, production dependencies installed, and pending migrations applied.\n'
