#!/usr/bin/env bash

set -euo pipefail

fail() {
    printf 'Release package validation failed: %s\n' "$1" >&2
    exit 1
}

[[ $# == 3 ]] || fail 'usage: verify-deploy-package.sh ARCHIVE TAG COMMIT'
archive=$1
tag=$2
commit=$3

[[ "$tag" =~ ^release-[0-9]+-[0-9]+-[0-9a-f]{12}$ ]] || fail 'tag has an unsupported format.'
[[ "$commit" =~ ^[0-9a-f]{40}$ ]] || fail 'commit must be a full lowercase Git SHA.'
[[ -f "$archive" && -r "$archive" ]] || fail 'archive is not a readable file.'

entries=$(tar -tzf "$archive") || fail 'archive is not a valid gzip tar file.'
[[ -n "$entries" ]] || fail 'archive is empty.'
while IFS= read -r entry; do
    [[ "$entry" != /* ]] || fail 'archive contains an absolute path.'
    [[ "/$entry/" != *'/../'* ]] || fail 'archive contains a parent path.'
    case "$entry" in
        .env|.env/*|*/.env|*/.env/*) fail 'archive contains an environment file.' ;;
        .specs|.specs/*|.github|.github/*|AGENTS.md|tests|tests/*|*/vendor|*/vendor/*|vendor|vendor/*)
            fail 'archive contains development-only files.'
            ;;
    esac
done <<< "$entries"

metadata=$(tar -xOzf "$archive" .deploy-release 2>/dev/null) \
    || fail 'archive is missing .deploy-release metadata.'
expected_metadata="$tag
$commit"
[[ "$metadata" == "$expected_metadata" ]] || fail 'archive tag or commit does not match the selected release.'

manifest=$(tar -xOzf "$archive" public/assets/build/.vite/manifest.json 2>/dev/null) \
    || fail 'archive is missing the Vite production manifest.'

php_bin=${PHP_BIN:-php}
assets=$(printf '%s' "$manifest" | "$php_bin" -r '
$manifest = json_decode(stream_get_contents(STDIN), true);
$entry = is_array($manifest) ? ($manifest["src/spa/main.js"] ?? null) : null;
if (!is_array($entry) || !is_string($entry["file"] ?? null) || !is_array($entry["css"] ?? null) || $entry["css"] === []) {
    fwrite(STDERR, "Release package validation failed: manifest has no SPA entry and stylesheet.\n");
    exit(1);
}
foreach (array_merge([$entry["file"]], $entry["css"]) as $asset) {
    if (!is_string($asset) || preg_match("/\\A[A-Za-z0-9_.\\/-]+\\z/D", $asset) !== 1 || str_starts_with($asset, "/") || str_contains("/" . $asset . "/", "/../")) {
        fwrite(STDERR, "Release package validation failed: manifest contains an unsafe asset path.\n");
        exit(1);
    }
    echo $asset, "\n";
}
') || fail 'Vite manifest validation failed.'
while IFS= read -r asset; do
    [[ -n "$asset" ]] || continue
    if ! grep -Fxq "public/assets/build/$asset" <<< "$entries" \
        && ! grep -Fxq "./public/assets/build/$asset" <<< "$entries"; then
        fail 'Vite manifest references a missing asset.'
    fi
done <<< "$assets"

printf 'OK: release package matches %s (%s) and contains its production SPA assets.\n' "$tag" "$commit"
