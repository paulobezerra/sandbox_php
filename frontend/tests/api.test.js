import { beforeEach, describe, expect, it, vi } from 'vitest';
import { api } from '../src/api/api.js';

const { request } = vi.hoisted(() => ({ request: vi.fn() }));
const requests = [];
let responses = [];

vi.mock('axios', async (importOriginal) => {
    const original = await importOriginal();
    return {
        ...original,
        default: {
            ...original.default,
            create: vi.fn(() => ({ request })),
        },
    };
});

describe('API client behavior', () => {
    beforeEach(() => {
        request.mockReset();
        requests.length = 0;
        responses = [];
        request.mockImplementation((config) => {
            requests.push(structuredClone(config));
            return Promise.resolve(responses.shift());
        });
        api.clearCsrf();
        window.appLocale = 'pt-BR';
    });

    it('sends the locale and refreshes a rejected CSRF token before retrying', async () => {
        responses.push(
            { status: 200, data: { csrfToken: 'first-token' } },
            { status: 419, data: { error: { message: 'expired' } } },
            { status: 200, data: { csrfToken: 'second-token' } },
            { status: 200, data: { ok: true } },
        );

        await expect(api.post('/api/posts', { title: 'Example' })).resolves.toEqual({ ok: true });

        expect(requests[0]).toEqual(expect.objectContaining({
            url: '/api/auth/csrf',
            headers: { 'Accept-Language': 'pt-BR' },
        }));
        expect(requests[1]).toEqual(expect.objectContaining({
            url: '/api/posts',
            headers: expect.objectContaining({ 'Accept-Language': 'pt-BR', 'X-CSRF-Token': 'first-token' }),
        }));
        expect(requests[2]).toEqual(expect.objectContaining({
            url: '/api/auth/csrf',
            headers: { 'Accept-Language': 'pt-BR' },
        }));
        expect(requests[3]).toEqual(expect.objectContaining({
            url: '/api/posts',
            headers: expect.objectContaining({ 'X-CSRF-Token': 'second-token' }),
        }));
    });

    it('dispatches authentication state changes after login and logout responses', async () => {
        const changed = vi.fn();
        window.addEventListener('auth-changed', changed);
        responses.push(
            { status: 200, data: { csrfToken: 'token' } },
            { status: 200, data: { user: { id: 7 } } },
            { status: 200, data: { message: 'signed out' } },
        );

        await api.post('/api/auth/login', { email: 'a@example.test', password: 'secret' });
        await api.post('/api/auth/logout');

        expect(changed).toHaveBeenCalledTimes(2);
        expect(changed.mock.calls.map(([event]) => event.detail)).toEqual([{ id: 7 }, null]);
        window.removeEventListener('auth-changed', changed);
    });

    it('uses the fallback locale, preserves custom headers, and handles 204 responses', async () => {
        window.appLocale = '';
        responses.push({ status: 204, data: null });
        await expect(api.request('/api/posts', { headers: { 'X-Trace': 'trace' } })).resolves.toBeNull();
        expect(requests[0]).toEqual(expect.objectContaining({
            url: '/api/posts',
            method: 'GET',
            headers: expect.objectContaining({ 'Accept-Language': 'en', 'X-Trace': 'trace' }),
        }));
    });

    it('rejects invalid response shapes and returns server error messages', async () => {
        responses.push({ status: 200, data: 'invalid' });
        await expect(api.get('/api/posts')).rejects.toThrow('client.invalidResponse');

        responses.push({ status: 422, data: { error: { message: 'title required' } } });
        await expect(api.get('/api/posts')).rejects.toThrow('title required');

        window.appTranslations = { 'client.httpFailure': 'HTTP {status}' };
        responses.push({ status: 500, data: {} });
        await expect(api.get('/api/posts')).rejects.toThrow('HTTP 500');
    });

    it('distinguishes network errors from non-Axios errors', async () => {
        window.appTranslations = { 'client.network': 'Network unavailable' };
        const networkFailure = Object.assign(new Error('socket'), { isAxiosError: true });
        request.mockRejectedValueOnce(networkFailure);
        await expect(api.get('/api/posts')).rejects.toThrow('Network unavailable');

        const unexpected = new Error('unexpected');
        request.mockRejectedValueOnce(unexpected);
        await expect(api.get('/api/posts')).rejects.toBe(unexpected);

        request.mockRejectedValueOnce(Object.assign(new Error('teapot'), {
            isAxiosError: true,
            response: { status: 418, data: { error: { message: 'teapot' } } },
        }));
        await expect(api.get('/api/posts')).rejects.toThrow('teapot');
    });

    it('rejects invalid CSRF responses and reports CSRF network failures', async () => {
        responses.push({ status: 403, data: {} });
        await expect(api.post('/api/posts', {})).rejects.toThrow('client.sessionPrepare');

        window.appTranslations = { 'client.csrfNetwork': 'Cannot prepare session' };
        request.mockRejectedValueOnce(Object.assign(new Error('offline'), { isAxiosError: true }));
        await expect(api.post('/api/posts', {})).rejects.toThrow('Cannot prepare session');
    });

    it('supports update and delete requests with the cached CSRF token', async () => {
        responses.push(
            { status: 200, data: { csrfToken: 'token' } },
            { status: 200, data: { updated: true } },
            { status: 204, data: null },
        );
        await expect(api.put('/api/posts/1', { title: 'Updated' })).resolves.toEqual({ updated: true });
        await expect(api.delete('/api/posts/1')).resolves.toBeNull();
        expect(requests[1]).toEqual(expect.objectContaining({ method: 'PUT', data: '{"title":"Updated"}' }));
        expect(requests[2]).toEqual(expect.objectContaining({ method: 'DELETE', url: '/api/posts/1' }));
    });
});
