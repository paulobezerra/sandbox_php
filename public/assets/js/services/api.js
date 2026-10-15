let csrfToken = null;

async function request(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = new Headers(options.headers || {});
    if (options.body !== undefined) headers.set('Content-Type', 'application/json');
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
        if (!csrfToken) {
            const csrfResponse = await fetch('/api/auth/csrf', { credentials: 'same-origin' });
            const csrfData = await csrfResponse.json();
            csrfToken = csrfData.csrfToken;
        }
        headers.set('X-CSRF-Token', csrfToken);
    }
    const response = await fetch(path, { ...options, method, headers, credentials: 'same-origin' });
    const data = response.status === 204 ? null : await response.json();
    if (!response.ok) throw new Error(data?.error?.message || `Falha HTTP ${response.status}`);
    if (data?.csrfToken) csrfToken = data.csrfToken;
    return data;
}

export const api = {
    request,
    clearCsrf: () => { csrfToken = null; },
    get: (path) => request(path),
    post: (path, body) => request(path, { method: 'POST', body: JSON.stringify(body ?? {}) }),
    put: (path, body) => request(path, { method: 'PUT', body: JSON.stringify(body ?? {}) }),
    delete: (path) => request(path, { method: 'DELETE' })
};
