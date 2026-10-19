#!/usr/bin/env bash
set -euo pipefail

project_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
php_bin=${PHP_BIN:-php}
composer_bin="$project_root/composer.phar"
fixture=$(mktemp -d)
trap 'rm -rf "$fixture"' EXIT

package="$fixture/package"
repository="$fixture/repository"
destination="$fixture/created"
mkdir -p "$package" "$repository"

composer_run() {
    GIT_CONFIG_COUNT=2 \
        GIT_CONFIG_KEY_0=safe.directory GIT_CONFIG_VALUE_0="$project_root" \
        GIT_CONFIG_KEY_1=safe.directory GIT_CONFIG_VALUE_1="$package" \
        "$php_bin" "$composer_bin" "$@"
}

# Build a tracked fixture so Composer's archive process can exercise exclusions
# against files that would otherwise be omitted merely because Git ignores them.
git -c safe.directory="$project_root" -C "$project_root" archive HEAD | tar -x -C "$package"
cp "$project_root/composer.json" "$package/composer.json"
cp "$project_root/.gitignore" "$package/.gitignore"
cp "$project_root/.gitattributes" "$package/.gitattributes"
cp "$project_root/.env.example" "$package/.env.example"
mkdir -p "$package/.specs/fixture" "$package/vendor/fixture" "$package/public/assets/vendor/swagger-ui"
printf 'secret=fixture\n' > "$package/.env"
printf 'skeleton context\n' > "$package/.specs/fixture/context.md"
printf 'dependency\n' > "$package/vendor/fixture/file.php"
printf 'generated\n' > "$package/public/assets/vendor/swagger-ui/file.js"
printf 'generated docs\n' > "$package/docs/swagger-ui.html"

git -C "$package" init -q
git -C "$package" config user.name 'Composer Package Test'
git -C "$package" config user.email 'composer-package-test@example.invalid'
git -C "$package" add -f .
git -C "$package" commit -qm fixture
git -C "$package" tag 1.0.0

composer_run --working-dir="$package" archive --format=zip --dir="$repository" --file=package --no-interaction
archive="$repository/package.zip"
test -f "$archive"

archive_contents=$(unzip -Z1 "$archive")
for expected in composer.json composer.lock .gitignore .gitattributes .env.example README.md src/Bootstrap/App.php public/index.php database/migrations; do
    grep -Fq "$expected" <<< "$archive_contents" \
        || { echo "Missing required archive content: $expected" >&2; exit 1; }
done

# GitHub's source archives follow git archive's export-ignore attributes.
git -C "$package" archive --format=zip --output="$repository/github-dist.zip" HEAD
github_contents=$(unzip -Z1 "$repository/github-dist.zip")
for forbidden in .env .specs vendor docs/swagger-ui.html public/assets/vendor/swagger-ui; do
    case "$forbidden" in
        .env|docs/swagger-ui.html) forbidden_pattern="(^|/)${forbidden}$" ;;
        vendor) forbidden_pattern="^vendor/.+" ;;
        *) forbidden_pattern="(^|/)${forbidden}/.+" ;;
    esac
    if grep -Eq "$forbidden_pattern" <<< "$github_contents"; then
        echo "Forbidden Git source archive content: $forbidden" >&2
        grep -E "$forbidden_pattern" <<< "$github_contents" >&2
        exit 1
    fi
done
for forbidden in .env .specs vendor docs/swagger-ui.html public/assets/vendor/swagger-ui; do
    case "$forbidden" in
        .env|docs/swagger-ui.html) forbidden_pattern="(^|/)${forbidden}$" ;;
        *) forbidden_pattern="(^|/)${forbidden}(/|$)" ;;
    esac
    if grep -Eq "$forbidden_pattern" <<< "$archive_contents"; then
        echo "Forbidden archive content: $forbidden" >&2
        grep -E "$forbidden_pattern" <<< "$archive_contents" >&2
        exit 1
    fi
done

archive_url=$("$php_bin" -r 'echo "file://" . realpath($argv[1]);' "$archive")
"$php_bin" -r '
$url = $argv[1];
$packages = ["packages" => ["paulobezerra/sandbox-php" => ["1.0.0" => [
    "name" => "paulobezerra/sandbox-php",
    "version" => "1.0.0",
    "type" => "project",
    "dist" => ["url" => $url, "type" => "zip"],
]]]];
file_put_contents($argv[2], json_encode($packages, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
' "$archive_url" "$repository/packages.json"

repository_url=$("$php_bin" -r 'echo "file://" . realpath($argv[1]);' "$repository")
composer_run create-project paulobezerra/sandbox-php "$destination" 1.0.0 \
    --repository="{\"type\":\"composer\",\"url\":\"$repository_url\"}" \
    --no-interaction --no-progress --remove-vcs --no-audit --ignore-platform-reqs --no-install

for expected in composer.json composer.lock .gitignore .env.example README.md src public database; do
    test -e "$destination/$expected" || { echo "Missing required package content: $expected" >&2; exit 1; }
done
for forbidden in .git .env vendor .specs docs/swagger-ui.html public/assets/vendor/swagger-ui; do
    test ! -e "$destination/$forbidden" || { echo "Forbidden package content: $forbidden" >&2; exit 1; }
done
composer_run --working-dir="$destination" install --no-interaction --no-progress --ignore-platform-reqs >/dev/null 2>&1
test -f "$destination/vendor/autoload.php"

mkdir "$fixture/populated"
printf 'preserve me\n' > "$fixture/populated/sentinel"
if composer_run create-project paulobezerra/sandbox-php "$fixture/populated" 1.0.0 \
    --repository="{\"type\":\"composer\",\"url\":\"$repository_url\"}" \
    --no-interaction --no-progress --remove-vcs --no-audit --ignore-platform-reqs --no-install >/dev/null 2>&1; then
    echo 'Composer unexpectedly accepted a populated destination.' >&2
    exit 1
fi
test "$(cat "$fixture/populated/sentinel")" = 'preserve me'

composer_run create-project paulobezerra/sandbox-php "$fixture/from-source" 1.0.0 \
    --repository="{\"type\":\"vcs\",\"url\":\"$package\"}" \
    --prefer-source --no-interaction --no-progress --remove-vcs --no-audit --ignore-platform-reqs --no-install >/dev/null
test ! -e "$fixture/from-source/.git"

mkdir "$fixture/missing-parent"
composer_run create-project paulobezerra/sandbox-php "$fixture/missing-parent/fresh" 1.0.0 \
    --repository="{\"type\":\"composer\",\"url\":\"$repository_url\"}" \
    --no-interaction --no-progress --remove-vcs --no-audit --ignore-platform-reqs --no-install >/dev/null
git -C "$fixture/missing-parent/fresh" init -q
test -z "$(git -C "$fixture/missing-parent/fresh" log --oneline 2>/dev/null || true)"
test -z "$(git -C "$fixture/missing-parent/fresh" remote)"

echo 'Composer package creation, exclusions, populated destination, and fresh Git checks passed.'
