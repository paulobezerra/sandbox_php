import axios from 'axios';

let csrfToken = null;

const http = axios.create({
    headers: { Accept: 'application/json' },
});

const t = (key, values = {}) => Object.entries(values).reduce(
    (message, [name, value]) => message.replace(`{${name}}`, String(value)),
    window.appTranslations?.[key] || key
);
const locale = () => window.appLocale || 'en';

async function send(config, networkMessage) {
    try {
        return await http.request(config);
    } catch (error) {
        if (!axios.isAxiosError(error)) throw error;
        if (!error.response) throw new Error(t(networkMessage));
        return error.response;
    }
}

function responseData(response) {
    if (response.status === 204) return null;
    if (response.data === null || typeof response.data !== 'object') {
        throw new Error(t('client.invalidResponse'));
    }
    return response.data;
}

function throwHttpError(response, data) {
    const message = typeof data?.error?.message === 'string'
        ? data.error.message
        : t('client.httpFailure', { status: response.status });
    throw new Error(message);
}

async function request(path, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = new Headers(options.headers || {});
    headers.set('Accept-Language', locale());
    if (options.body !== undefined) headers.set('Content-Type', 'application/json');

    const mutating = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);
    if (mutating) {
        if (!csrfToken) await refreshCsrf();
        headers.set('X-CSRF-Token', csrfToken);
    }

    const config = { url: path, method, data: options.body, headers: Object.fromEntries(headers.entries()) };
    let response = await send(config, 'client.network');
    if (response.status === 419 && mutating) {
        csrfToken = null;
        await refreshCsrf();
        config.headers['X-CSRF-Token'] = csrfToken;
        response = await send(config, 'client.network');
    }

    const data = responseData(response);
    if (response.status < 200 || response.status >= 300) throwHttpError(response, data);
    if (data?.csrfToken) csrfToken = data.csrfToken;
    if (path === '/api/auth/login' && data?.user) window.dispatchEvent(new CustomEvent('auth-changed', { detail: data.user }));
    if (path === '/api/auth/logout') window.dispatchEvent(new CustomEvent('auth-changed', { detail: null }));
    return data;
}

async function refreshCsrf() {
    const response = await send({
        url: '/api/auth/csrf',
        method: 'GET',
        headers: { 'Accept-Language': locale() },
    }, 'client.csrfNetwork');
    const data = responseData(response);
    if (response.status < 200 || response.status >= 300 || typeof data?.csrfToken !== 'string' || data.csrfToken.length === 0) {
        const message = typeof data?.error?.message === 'string'
            ? data.error.message
            : t('client.sessionPrepare', { status: response.status });
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
