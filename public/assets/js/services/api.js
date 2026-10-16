let csrfToken = null;

const t = (key, values = {}) => Object.entries(values).reduce(
    (message, [name, value]) => message.replace(`{${name}}`, String(value)),
    window.appTranslations?.[key] || key
);
const locale = () => window.appLocale || 'en';

async function request(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = new Headers(options.headers || {});
    headers.set('Accept-Language', locale());
    if (options.body !== undefined) headers.set('Content-Type', 'application/json');
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        if (!csrfToken) await refreshCsrf();
        headers.set('X-CSRF-Token', csrfToken);
    }
    let response;
    try {
        response = await fetch(path, { ...options, method, headers, credentials: 'same-origin' });
    } catch {
        throw new Error(t('client.network'));
    }
    if (response.status === 419 && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        csrfToken = null;
        await refreshCsrf();
        headers.set('X-CSRF-Token', csrfToken);
        try {
            response = await fetch(path, { ...options, method, headers, credentials: 'same-origin' });
        } catch {
            throw new Error(t('client.network'));
        }
    }
    let data = null;
    if (response.status !== 204) {
        try {
            data = await response.json();
        } catch {
            throw new Error(t('client.invalidResponse'));
        }
    }
    if (!response.ok) {
        const message = typeof data?.error?.message === 'string' ? data.error.message : t('client.httpFailure', {status: response.status});
        throw new Error(message);
    }
    if (data?.csrfToken) csrfToken = data.csrfToken;
    if (path === '/api/auth/login' && data?.user) window.dispatchEvent(new CustomEvent('auth-changed', {detail: data.user}));
    if (path === '/api/auth/logout') window.dispatchEvent(new CustomEvent('auth-changed', {detail: null}));
    return data;
}

async function refreshCsrf() {
    let response;
    try {
        response = await fetch('/api/auth/csrf', { credentials: 'same-origin', headers: { 'Accept-Language': locale() } });
    } catch {
        throw new Error(t('client.csrfNetwork'));
    }
    let data;
    try {
        data = await response.json();
    } catch {
        throw new Error(t('client.invalidResponse'));
    }
    if (!response.ok || typeof data?.csrfToken !== 'string' || data.csrfToken.length === 0) {
        const message = typeof data?.error?.message === 'string' ? data.error.message : t('client.sessionPrepare', {status: response.status});
        throw new Error(message);
    }
    csrfToken = data.csrfToken;
}

export const api = {
    request,
    clearCsrf: () => { csrfToken = null; },
    get: (path) => request(path),
    post: (path, body) => request(path, { method: 'POST', body: JSON.stringify(body ?? {}) }),
    put: (path, body) => request(path, { method: 'PUT', body: JSON.stringify(body ?? {}) }),
    delete: (path) => request(path, { method: 'DELETE' })
};
