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
});
