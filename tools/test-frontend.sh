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
    echo "Chrome/Chromium not found. Set CHROME_BIN or install a Chromium browser." >&2
    exit 1
fi

work_dir="$(mktemp -d)"
fixture_id="codex-frontend-$BASHPID"
fixture_html="public/assets/$fixture_id.html"
fixture_js="public/assets/$fixture_id.js"
failure_html="public/assets/${fixture_id}-failure.html"
failure_js="public/assets/${fixture_id}-failure.js"
cleanup() {
    rm -rf "$work_dir"
    rm -f "$fixture_html" "$fixture_js" "$failure_html" "$failure_js"
}
trap cleanup EXIT

check_page() {
    local path="$1"
    local expected="$2"
    local label="$3"
    local browser_locale="${4:-en-US}"
    local expected_locale="${5:-en}"
    local page_file="$work_dir/page.html"
    local error_file="$work_dir/chrome.log"

    if ! "$chrome_bin" \
        --headless=new \
        --lang="$browser_locale" \
        --accept-lang="$browser_locale" \
        --no-sandbox \
        --disable-gpu \
        --disable-dev-shm-usage \
        --no-first-run \
        --no-default-browser-check \
        --user-data-dir="$work_dir/profile" \
        --virtual-time-budget=4000 \
        --dump-dom "$base_url$path" >"$page_file" 2>"$error_file"; then
        echo "FAILED: $label did not open in Chromium ($path)." >&2
        cat "$error_file" >&2
        return 1
    fi

    if ! grep -Fq 'data-v-app' "$page_file" || ! grep -Fq "$expected" "$page_file" || ! grep -Fq "<html lang=\"$expected_locale\"" "$page_file"; then
        echo "FAILED: Vue did not mount the expected view: $label ($path)." >&2
        cat "$error_file" >&2
        cat "$page_file" >&2
        return 1
    fi
    if grep -Fq 'role="status">Loading the application…' "$page_file" || grep -Fq 'role="status">Carregando a aplicação…' "$page_file"; then
        echo "FAILED: the loading indicator remained visible after mount: $label ($path)." >&2
        cat "$page_file" >&2
        return 1
    fi

    printf 'OK: %s (%s)\n' "$label" "$path"
}

boot_file="$work_dir/boot.html"
if ! curl --fail --silent --show-error \
    --header 'Accept-Language: en-US' \
    "$base_url/" >"$boot_file"; then
    echo 'FAILED: the application boot state did not load.' >&2
    exit 1
fi
if ! grep -Eq '<div[^>]+id="app"[^>]+v-cloak' "$boot_file" \
    || ! grep -Fq 'role="status">Loading the application…' "$boot_file"; then
    echo 'FAILED: the application boot state exposes its uncompiled Vue template.' >&2
    cat "$boot_file" >&2
    exit 1
fi
printf 'OK: loading state cloaks the uncompiled Vue template\n'

check_page / 'A reusable foundation for PHP applications' 'English documentation Home'
check_page / 'Installation and first use' 'Home installation section'
check_page / 'Registration, login, and the Posts workflow' 'Posts workflow on Home'
check_page / 'SDD Lite' 'SDD Lite on Home'
check_page / 'Uma base reutilizável para aplicações PHP' 'Portuguese documentation Home' pt-BR pt-BR
check_page /features 'Page not found' 'removed features page route'
check_page /architecture 'Page not found' 'removed architecture page route'
check_page /guide 'Page not found' 'removed guide page route'
check_page /about 'Page not found' 'removed About page route'
check_home_details() {
    local browser_locale="$1"
    local expected_locale="$2"
    local repository_label="$3"
    local guide_label="$4"
    local foundation_text="$5"
    local examples_text="$6"
    local home_file="$work_dir/home-$expected_locale.html"
    local error_file="$work_dir/home-$expected_locale-chrome.log"

    if ! "$chrome_bin" \
        --headless=new \
        --lang="$browser_locale" \
        --accept-lang="$browser_locale" \
        --no-sandbox \
        --disable-gpu \
        --disable-dev-shm-usage \
        --no-first-run \
        --no-default-browser-check \
        --user-data-dir="$work_dir/home-profile-$expected_locale" \
        --virtual-time-budget=4000 \
        --dump-dom "$base_url/" >"$home_file" 2>"$error_file"; then
        echo "FAILED: the Home did not open in Chromium for $expected_locale checks." >&2
        cat "$error_file" >&2
        return 1
    fi

    for anchor in capacidades stack arquitetura instalacao primeiro-uso rotas qualidade producao customizacao sdd-lite; do
        if ! grep -Fq "id=\"$anchor\"" "$home_file" || ! grep -Fq "href=\"#$anchor\"" "$home_file"; then
            echo "FAILED: missing Home anchor or target in $expected_locale: $anchor." >&2
            return 1
        fi
    done
    if ! grep -Fq "href=\"https://github.com/paulobezerra/sandbox_php\"" "$home_file" \
        || ! grep -Fq ">$repository_label</a>" "$home_file" \
        || ! grep -Fq 'href="https://github.com/paulobezerra/sandbox_php/blob/main/docs/customization.md"' "$home_file" \
        || ! grep -Fq ">$guide_label</a>" "$home_file"; then
        echo "FAILED: missing localized repository or customization link in $expected_locale." >&2
        return 1
    fi
    if ! grep -Fq 'test-all.sh' "$home_file" || ! grep -Fq 'migrate:status' "$home_file" \
        || ! grep -Fq 'composer format' "$home_file" || ! grep -Fq 'composer quality' "$home_file"; then
        echo "FAILED: missing operational guidance in $expected_locale." >&2
        return 1
    fi
    local foundation_position
    local examples_position
    foundation_position="$(grep -boF "$foundation_text" "$home_file" | head -n 1 | cut -d: -f1)"
    examples_position="$(grep -boF "$examples_text" "$home_file" | head -n 1 | cut -d: -f1)"
    if [[ -z "$foundation_position" || -z "$examples_position" || "$foundation_position" -ge "$examples_position" ]]; then
        echo "FAILED: the reusable foundation must be introduced before Auth/Posts examples in $expected_locale." >&2
        return 1
    fi
    if grep -Eq 'href="/(about|features|architecture|guide)([/?#"]|$)' "$home_file"; then
        echo "FAILED: the Home still links to a removed institutional page in $expected_locale." >&2
        return 1
    fi
    printf 'OK: Home anchors, links, and operational guidance (%s)\n' "$expected_locale"
}

check_home_details en-US en 'View the sandbox_php repository on GitHub' 'Read the User/Auth customization and domain extension guide' 'A reusable PHP, Slim, and Vue foundation' 'Registration, login, and the Posts workflow'
check_home_details pt-BR pt-BR 'Acesse o repositório sandbox_php no GitHub' 'Leia o guia para customizar User/Auth e adicionar domínios' 'Uma base reutilizável em PHP, Slim e Vue' 'Cadastro, login e ciclo de Posts'
check_page /blog 'Blog' 'English public blog'
check_page /blog 'Blog' 'Portuguese public blog' pt-BR pt-BR
check_page /uma-pagina-antiga 'Page not found' 'removed dynamic Pages route'
check_page /my-posts 'My Posts' 'English Posts editor'
check_page /my-posts 'Meus Posts' 'Portuguese Posts editor' pt-BR pt-BR
check_page /admin/content 'Page not found' 'removed admin route'
check_page /login 'Sign in' 'English login'
check_page /login 'type="email"' 'email field on login'
check_page /register 'Create account' 'English registration'
check_page /register 'type="email"' 'email field on registration'
check_page /login 'Entrar' 'Portuguese login' pt-BR pt-BR
check_page /register 'Criar conta' 'Portuguese registration' pt-BR pt-BR

cat > "$fixture_html" <<EOF
<!doctype html>
<html lang="en">
<body>
<div id="app" data-locale="en" aria-busy="true"><p id="boot-message" role="status">Loading</p><script type="application/json" id="initial-translations">{"shell.loadError":"The application could not load its language data."}</script><aside class="sidebar"><nav>
<router-link to="/">Home</router-link><router-link to="/blog">Blog</router-link>
<router-link v-if="!user" to="/login">Log in</router-link>
<router-link v-if="user" to="/my-posts">My Posts</router-link>
<button v-if="user" class="nav-button" type="button" @click="logout">Log out</button>
</nav></aside><main><router-view></router-view></main></div>
<div id="test-app"><div id="xss-check" v-html="html"></div></div>
<div id="auth-test"></div>
<pre id="test-status">running</pre>
<script src="/assets/vendor/vue.global.prod.js"></script>
<script src="/assets/vendor/vue-router.global.prod.js"></script>
<script type="module" src="/assets/$fixture_id.js"></script>
</body>
</html>
EOF
cat > "$fixture_js" <<'EOF'
import {api} from '/assets/js/services/api.js';

window.appLocale = document.querySelector('#app').dataset.locale;
window.appTranslations = {
    'client.network': 'Could not connect to the server. Check your connection and try again.',
    'client.csrfNetwork': 'Could not refresh session protection. Check your connection.',
    'client.invalidResponse': 'The server returned an invalid response.',
    'auth.account': 'ACCOUNT', 'auth.loginTitle': 'Sign in', 'auth.registerTitle': 'Create account',
    'auth.name': 'Name', 'auth.email': 'Email', 'auth.password': 'Password', 'auth.passwordHint': 'Use at least 12 characters.',
    'auth.submitting': 'Submitting…', 'auth.createdNotice': 'Account created. You can now sign in.',
    'auth.createAccount': 'Create account', 'auth.backToLogin': 'Back to sign in'
};
const app = Vue.createApp({
    data: () => ({html: '&lt;img src=x onerror=alert(1)&gt; &lt;script&gt;alert(1)&lt;/script&gt; <a>unsafe link</a>'}),
    mounted: async () => {
        const status = document.querySelector('#test-status');
        try {
            window.originalFetch = window.fetch.bind(window);
            let csrfCalls = 0;
            let mutationCalls = 0;
            let failCsrfNetwork = false;
            let failMutationNetwork = false;
            window.fetch = async (url, options = {}) => {
                if (url.startsWith('/assets/i18n/')) return window.originalFetch(url, options);
                if (url.startsWith('/api/') && new Headers(options.headers || {}).get('Accept-Language') !== window.appLocale) throw new Error('SPA request did not preserve the selected locale');
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
                if (url === '/api/test-locale') return new Response(JSON.stringify({ok: true}), {status: 200});
                if (url === '/api/invalid-test') return new Response('not json', {status: 200});
                if (url === '/api/auth/register') return new Response(JSON.stringify({user: {id: 9, name: 'Alice', email: 'alice@example.test'}}), {status: 201});
                if (url === '/api/auth/login') return new Response(JSON.stringify({user: {id: 9, name: 'Alice', email: 'alice@example.test'}, csrfToken: 'login-token'}), {status: 200});
                if (url === '/api/auth/logout') return new Response(JSON.stringify({message: window.appLocale === 'pt-BR' ? 'Você saiu da sua conta.' : 'You have signed out.'}), {status: 200});
                if (url === '/api/network-test' && failMutationNetwork) throw new TypeError('offline');
                throw new Error(`Unexpected request ${url}`);
            };

            const result = await api.post('/api/test');
            if (!result.ok || csrfCalls !== 2 || mutationCalls !== 2) throw new Error('419 retry did not refresh and retry exactly once');
            window.appLocale = 'pt-BR';
            window.appTranslations = {
                'client.csrfNetwork': 'Não foi possível atualizar a proteção da sessão. Verifique sua conexão.',
                'client.invalidResponse': 'O servidor retornou uma resposta inválida.'
            };
            const localized = await api.get('/api/test-locale');
            if (!localized.ok) throw new Error('localized API request failed');
            let invalidResponseError = '';
            try { await api.get('/api/invalid-test'); } catch (error) { invalidResponseError = error.message; }
            if (!invalidResponseError.includes('resposta inválida')) throw new Error('invalid API response was not localized');
            api.clearCsrf();
            failCsrfNetwork = true;
            let localizedCsrfError = '';
            try { await api.post('/api/network-test'); } catch (error) { localizedCsrfError = error.message; }
            if (!localizedCsrfError.includes('atualizar a proteção da sessão')) throw new Error('CSRF network failure was not localized');
            window.appLocale = 'en';
            window.appTranslations = {
                'client.network': 'Could not connect to the server. Check your connection and try again.',
                'client.csrfNetwork': 'Could not refresh session protection. Check your connection.',
                'client.invalidResponse': 'The server returned an invalid response.',
                'auth.account': 'ACCOUNT', 'auth.loginTitle': 'Sign in', 'auth.registerTitle': 'Create account',
                'auth.name': 'Name', 'auth.email': 'Email', 'auth.password': 'Password', 'auth.passwordHint': 'Use at least 12 characters.',
                'auth.submitting': 'Submitting…', 'auth.createdNotice': 'Account created. You can now sign in.',
                'auth.createAccount': 'Create account', 'auth.backToLogin': 'Back to sign in'
            };
            failCsrfNetwork = false;
            failMutationNetwork = true;
            await api.get('/api/test-locale');
            let networkError = '';
            try { await api.post('/api/network-test'); } catch (error) { networkError = error.message; }
            if (!networkError.includes('Could not connect to the server')) throw new Error('API network failure was not reported');

            const {default: Auth} = await import('/assets/js/pages/Auth.js');
            const authRouter = VueRouter.createRouter({
                history: VueRouter.createMemoryHistory(),
                routes: [
                    {path: '/register', component: Auth, meta: {authMode: 'register'}},
                    {path: '/login', component: Auth, meta: {authMode: 'login'}},
                    {path: '/my-posts', component: {template: '<p>My Posts</p>'}}
                ]
            });
            const authApp = Vue.createApp({template: '<router-view />'});
            authApp.config.globalProperties.$t = key => window.appTranslations[key] || key;
            authApp.use(authRouter);
            await authRouter.push('/register');
            await authRouter.isReady();
            authApp.mount('#auth-test');
            const authRoot = document.querySelector('#auth-test');
            const fill = (selector, value) => {
                const input = authRoot.querySelector(selector);
                input.value = value;
                input.dispatchEvent(new Event('input', {bubbles: true}));
            };
            fill('#name', 'Alice');
            fill('#email', 'alice@example.test');
            fill('#password', 'long-enough-password');
            authRoot.querySelector('form').dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
            await new Promise(resolve => setTimeout(resolve, 100));
            await Vue.nextTick();
            if (!authRoot.textContent.includes('Account created. You can now sign in.')) throw new Error('English registration success was not localized');
            await authRouter.push('/login');
            await Vue.nextTick();
            fill('#email', 'alice@example.test');
            fill('#password', 'long-enough-password');
            authRoot.querySelector('form').dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
            await new Promise(resolve => setTimeout(resolve, 100));
            await Vue.nextTick();
            if (authRouter.currentRoute.value.path !== '/my-posts') throw new Error('English login did not navigate to My Posts');
            const englishLogout = await api.post('/api/auth/logout');
            if (englishLogout.message !== 'You have signed out.') throw new Error('English logout response did not use the selected locale');
            window.appLocale = 'pt-BR';
            window.appTranslations = {
                'client.network': 'Não foi possível conectar ao servidor.',
                'client.csrfNetwork': 'Não foi possível atualizar a proteção da sessão.',
                'client.invalidResponse': 'O servidor retornou uma resposta inválida.',
                'auth.account': 'CONTA', 'auth.loginTitle': 'Entrar', 'auth.registerTitle': 'Criar conta',
                'auth.name': 'Nome', 'auth.email': 'E-mail', 'auth.password': 'Senha', 'auth.passwordHint': 'Use pelo menos 12 caracteres.',
                'auth.submitting': 'Enviando…', 'auth.createdNotice': 'Conta criada. Agora você pode entrar.',
                'auth.createAccount': 'Criar conta', 'auth.backToLogin': 'Voltar para entrar'
            };
            await authRouter.push('/register');
            await Vue.nextTick();
            fill('#name', 'Alice');
            fill('#email', 'alice@example.test');
            fill('#password', 'long-enough-password');
            authRoot.querySelector('form').dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
            await new Promise(resolve => setTimeout(resolve, 100));
            await Vue.nextTick();
            if (!authRoot.textContent.includes('Conta criada. Agora você pode entrar.')) throw new Error('Portuguese registration success was not localized');
            await authRouter.push('/login');
            await Vue.nextTick();
            if (!authRoot.textContent.includes('Entrar') || !authRoot.textContent.includes('E-mail')) throw new Error('Portuguese login view was not localized');
            fill('#email', 'alice@example.test');
            fill('#password', 'long-enough-password');
            authRoot.querySelector('form').dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
            await new Promise(resolve => setTimeout(resolve, 100));
            await Vue.nextTick();
            if (authRouter.currentRoute.value.path !== '/my-posts') throw new Error('localized login did not navigate to My Posts');
            const logout = await api.post('/api/auth/logout');
            if (logout.message !== 'Você saiu da sua conta.') throw new Error('logout response did not use the selected locale');
            if (document.querySelector('#xss-check img, #xss-check script') !== null) throw new Error('escaped Markdown created executable elements');
            if (!document.querySelector('#xss-check').textContent.includes('<img src=x onerror=alert(1)>')) throw new Error('escaped Markdown was not preserved as text');

            await import('/assets/js/app.js');
            await new Promise(resolve => setTimeout(resolve, 0));
            if (!document.querySelector('#app').textContent.includes('My Posts') || document.querySelector('#app').textContent.includes('Log in')) throw new Error('restored session did not update the navigation');
            window.dispatchEvent(new CustomEvent('auth-changed', {detail: null}));
            await Vue.nextTick();
            if (!document.querySelector('#app').textContent.includes('Log in') || document.querySelector('#app').textContent.includes('My Posts')) throw new Error('logout did not update the navigation');
            status.textContent = 'PASS: CSRF retry once and escaped Markdown is inert';
        } catch (error) {
            status.textContent = `FAIL: ${error.message}`;
        }
    }
});
app.mount('#test-app');
EOF
check_page "/assets/$fixture_id.html" 'PASS: CSRF retry once and escaped Markdown is inert' 'CSRF retry, escaped HTML, and session navigation'

cat > "$failure_html" <<EOF
<!doctype html><html lang="en"><body>
<div id="app" data-locale="en" aria-busy="true"><p id="boot-message">Loading</p><script type="application/json" id="initial-translations">{"shell.loadError":"The application could not load its language data. Please reload the page."}</script></div>
<script src="/assets/vendor/vue.global.prod.js"></script><script src="/assets/vendor/vue-router.global.prod.js"></script>
<script type="module" src="/assets/${fixture_id}-failure.js"></script></body></html>
EOF
cat > "$failure_js" <<'EOF'
window.fetch = async () => { throw new TypeError('catalogue unavailable'); };
try { await import('/assets/js/app.js'); } catch {}
EOF
if ! "$chrome_bin" --headless=new --lang=en-US --accept-lang=en-US --no-sandbox --disable-gpu --disable-dev-shm-usage --no-first-run --no-default-browser-check --user-data-dir="$work_dir/failure-profile" --virtual-time-budget=4000 --dump-dom "$base_url/assets/${fixture_id}-failure.html" >"$work_dir/failure.html" 2>"$work_dir/failure.log"; then
    echo 'FAILED: the catalog failure stopped the page without rendering the fallback.' >&2
    cat "$work_dir/failure.log" >&2
    exit 1
fi
if ! grep -Fq 'The application could not load its language data. Please reload the page.' "$work_dir/failure.html" || grep -Fq 'data-v-app' "$work_dir/failure.html"; then
    echo 'FAILED: the catalog failure did not show the expected safe fallback.' >&2
    exit 1
fi
printf 'OK: visible fallback when the catalog fails to load\n'
