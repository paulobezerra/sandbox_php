#!/usr/bin/env bash
set -euo pipefail

base_url="${1:-http://127.0.0.1:8080}"
chrome_bin="${CHROME_BIN:-}"
if [[ -z "$chrome_bin" ]]; then
    for candidate in google-chrome chromium chromium-browser; do
        if command -v "$candidate" >/dev/null 2>&1; then
            chrome_bin="$candidate"
            break
        fi
    done
fi

if [[ -z "$chrome_bin" ]]; then
    echo "Chrome/Chromium não encontrado. Defina CHROME_BIN ou instale um navegador Chromium." >&2
    exit 1
fi

work_dir="$(mktemp -d)"
trap 'rm -rf "$work_dir"' EXIT

check_page() {
    local path="$1"
    local expected="$2"
    local label="$3"
    local page_file="$work_dir/page.html"
    local error_file="$work_dir/chrome.log"

    if ! "$chrome_bin" \
        --headless=new \
        --no-sandbox \
        --disable-gpu \
        --disable-dev-shm-usage \
        --no-first-run \
        --no-default-browser-check \
        --user-data-dir="$work_dir/profile" \
        --virtual-time-budget=4000 \
        --dump-dom "$base_url$path" >"$page_file" 2>"$error_file"; then
        echo "FALHOU: $label não abriu no Chromium ($path)." >&2
        cat "$error_file" >&2
        return 1
    fi

    if ! grep -Fq 'data-v-app' "$page_file" || ! grep -Fq "$expected" "$page_file"; then
        echo "FALHOU: Vue não montou a tela esperada: $label ($path)." >&2
        cat "$error_file" >&2
        return 1
    fi

    printf 'OK: %s (%s)\n' "$label" "$path"
}

check_page / 'Uma base clara para tirar aplicações PHP do zero.' 'home institucional'
check_page /features 'Uma base funcional para aplicações PHP' 'recursos'
check_page /architecture 'Dependências seguem uma direção simples' 'arquitetura'
check_page /guide 'Comece a desenvolver' 'guia de instalação'
check_page /blog 'Blog' 'blog público'
check_page /admin/content 'type="email"' 'administração redireciona ao login compartilhado'
check_page /login 'Entrar' 'login'
check_page /login 'type="email"' 'campo de e-mail no login'
check_page /register 'Criar conta' 'cadastro'
check_page /register 'type="email"' 'campo de e-mail no cadastro'
check_page /forgot-password 'Recuperar senha' 'recuperação de senha'
check_page /reset-password 'Redefinir senha' 'redefinição de senha'
check_page /account/recovery-codes 'type="email"' 'códigos de recuperação exige login e retorna ao formulário compartilhado'
