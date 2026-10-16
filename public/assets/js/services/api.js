let csrfToken = null;

async function request(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = new Headers(options.headers || {});
    if (options.body !== undefined) headers.set('Content-Type', 'application/json');
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        if (!csrfToken) await refreshCsrf();
        headers.set('X-CSRF-Token', csrfToken);
    }
    let response;
    try {
        response = await fetch(path, { ...options, method, headers, credentials: 'same-origin' });
    } catch {
        throw new Error('Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.');
    }
    if (response.status === 419 && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        csrfToken = null;
        await refreshCsrf();
        headers.set('X-CSRF-Token', csrfToken);
        try {
            response = await fetch(path, { ...options, method, headers, credentials: 'same-origin' });
        } catch {
            throw new Error('Não foi possível conectar ao servidor. Verifique sua conexão e tente novamente.');
        }
    }
    const data = response.status === 204 ? null : await response.json().catch(() => null);
    if (!response.ok) throw new Error(data?.error?.message || `Falha HTTP ${response.status}`);
    if (data?.csrfToken) csrfToken = data.csrfToken;
    if (path === '/api/auth/login' && data?.user) window.dispatchEvent(new CustomEvent('auth-changed', {detail: data.user}));
    if (path === '/api/auth/logout') window.dispatchEvent(new CustomEvent('auth-changed', {detail: null}));
    return data;
}

async function refreshCsrf() {
    let response;
    try {
        response = await fetch('/api/auth/csrf', { credentials: 'same-origin' });
    } catch {
        throw new Error('Não foi possível obter a proteção da sessão. Verifique sua conexão.');
    }
    const data = await response.json().catch(() => null);
    if (!response.ok || typeof data?.csrfToken !== 'string' || data.csrfToken.length === 0) {
        throw new Error(data?.error?.message || `Falha ao preparar a sessão (HTTP ${response.status}).`);
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
