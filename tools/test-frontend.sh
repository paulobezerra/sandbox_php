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
fixture_id="codex-frontend-$BASHPID"
fixture_html="public/assets/$fixture_id.html"
fixture_js="public/assets/$fixture_id.js"
cleanup() {
    rm -rf "$work_dir"
    rm -f "$fixture_html" "$fixture_js"
}
trap cleanup EXIT

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

check_page / 'Uma base reutilizável para aplicações PHP' 'home documental'
check_page / 'Instalação e primeiro uso' 'instalação e primeiro uso na Home'
check_page / 'Cadastro, login e ciclo de Posts' 'uso de Posts na Home'
check_page / 'SDD Lite' 'SDD Lite na Home'
check_page /features 'Página não encontrada' 'rota institucional antiga de recursos removida'
check_page /architecture 'Página não encontrada' 'rota institucional antiga de arquitetura removida'
check_page /guide 'Página não encontrada' 'rota institucional antiga de guia removida'
check_page /about 'Página não encontrada' 'rota institucional antiga Sobre removida'
home_file="$work_dir/home.html"
if ! "$chrome_bin" \
    --headless=new \
    --no-sandbox \
    --disable-gpu \
    --disable-dev-shm-usage \
    --no-first-run \
    --no-default-browser-check \
    --user-data-dir="$work_dir/home-profile" \
    --virtual-time-budget=4000 \
    --dump-dom "$base_url/" >"$home_file" 2>"$work_dir/home-chrome.log"; then
    echo 'FALHOU: a Home não abriu no Chromium para conferir suas âncoras.' >&2
    cat "$work_dir/home-chrome.log" >&2
    exit 1
fi
for anchor in capacidades stack arquitetura instalacao primeiro-uso rotas qualidade producao customizacao sdd-lite; do
    if ! grep -Fq "id=\"$anchor\"" "$home_file" || ! grep -Fq "href=\"#$anchor\"" "$home_file"; then
        echo "FALHOU: âncora ausente ou sem destino na Home: $anchor." >&2
        exit 1
    fi
done
if grep -Eq 'href="/(about|features|architecture|guide)([/?#"]|$)' "$home_file"; then
    echo 'FALHOU: a Home ainda aponta para uma página institucional removida.' >&2
    exit 1
fi
printf 'OK: âncoras da Home e links internos\n'
check_page /blog 'Blog' 'blog público'
check_page /uma-pagina-antiga 'Página não encontrada' 'rota dinâmica de Pages removida'
check_page /my-posts 'Meus Posts' 'editor de posts autenticado'
check_page /admin/content 'Página não encontrada' 'rota administrativa removida'
check_page /login 'Entrar' 'login'
check_page /login 'type="email"' 'campo de e-mail no login'
check_page /register 'Criar conta' 'cadastro'
check_page /register 'type="email"' 'campo de e-mail no cadastro'

cat > "$fixture_html" <<EOF
<!doctype html>
<html lang="pt-BR">
<body>
<div id="app"><aside class="sidebar"><nav>
<router-link to="/">Início</router-link><router-link to="/blog">Blog</router-link>
<router-link v-if="!user" to="/login">Entrar</router-link>
<router-link v-if="user" to="/my-posts">Meus Posts</router-link>
<button v-if="user" class="nav-button" type="button" @click="logout">Sair</button>
</nav></aside><main><router-view></router-view></main></div>
<div id="test-app"><div id="xss-check" v-html="html"></div></div>
<pre id="test-status">running</pre>
<script src="/assets/vendor/vue.global.prod.js"></script>
<script src="/assets/vendor/vue-router.global.prod.js"></script>
<script type="module" src="/assets/$fixture_id.js"></script>
</body>
</html>
EOF
cat > "$fixture_js" <<'EOF'
import {api} from '/assets/js/services/api.js';

const app = Vue.createApp({
    data: () => ({html: '&lt;img src=x onerror=alert(1)&gt; &lt;script&gt;alert(1)&lt;/script&gt; <a>unsafe link</a>'}),
    mounted: async () => {
        const status = document.querySelector('#test-status');
        try {
            let csrfCalls = 0;
            let mutationCalls = 0;
            let failCsrfNetwork = false;
            window.fetch = async (url, options = {}) => {
                if (url === '/api/auth/me') return new Response(JSON.stringify({user: {id: 7, name: 'Autor', email: 'author@example.test'}, csrfToken: 'menu-token'}), {status: 200});
                if (url === '/api/auth/csrf') {
                    if (failCsrfNetwork) throw new TypeError('offline');
                    csrfCalls += 1;
                    return new Response(JSON.stringify({csrfToken: `token-${csrfCalls}`}), {status: 200});
                }
                if (url === '/api/test') {
                    mutationCalls += 1;
                    if (mutationCalls === 1) return new Response(JSON.stringify({error: {message: 'expired'}}), {status: 419});
                    if (options.headers.get('X-CSRF-Token') !== 'token-2') return new Response('{}', {status: 403});
                    return new Response(JSON.stringify({ok: true}), {status: 200});
                }
                throw new Error(`Unexpected request ${url}`);
            };

            const result = await api.post('/api/test');
            if (!result.ok || csrfCalls !== 2 || mutationCalls !== 2) throw new Error('419 retry did not refresh and retry exactly once');
            if (document.querySelector('#xss-check img, #xss-check script') !== null) throw new Error('escaped Markdown created executable elements');
            if (!document.querySelector('#xss-check').textContent.includes('<img src=x onerror=alert(1)>')) throw new Error('escaped Markdown was not preserved as text');

            api.clearCsrf();
            failCsrfNetwork = true;
            let networkError = '';
            try { await api.post('/api/network-test'); } catch (error) { networkError = error.message; }
            if (!networkError.includes('proteção da sessão')) throw new Error('CSRF network failure was not reported');

            await import('/assets/js/app.js');
            await new Promise(resolve => setTimeout(resolve, 0));
            if (!document.querySelector('#app').textContent.includes('Meus Posts') || document.querySelector('#app').textContent.includes('Entrar')) throw new Error('restored session did not update the navigation');
            window.dispatchEvent(new CustomEvent('auth-changed', {detail: null}));
            await Vue.nextTick();
            if (!document.querySelector('#app').textContent.includes('Entrar') || document.querySelector('#app').textContent.includes('Meus Posts')) throw new Error('logout did not update the navigation');
            status.textContent = 'PASS: CSRF retry once and escaped Markdown is inert';
        } catch (error) {
            status.textContent = `FAIL: ${error.message}`;
        }
    }
});
app.mount('#test-app');
EOF
check_page "/assets/$fixture_id.html" 'PASS: CSRF retry once and escaped Markdown is inert' 'retry CSRF, HTML escapado e navegação por sessão'
