import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import Auth from '../src/spa/pages/Auth.vue';

const { post } = vi.hoisted(() => ({ post: vi.fn() }));

vi.mock('../src/api/api.js', () => ({ api: { post } }));

const InputStub = {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)">',
};

function mountAuth(mode) {
    const router = { push: vi.fn() };
    const wrapper = mount(Auth, {
        global: {
            mocks: {
                $route: { meta: { authMode: mode }, query: { redirect: '/private-area' } },
                $router: router,
                $t: (key) => ({
                    'auth.loginTitle': 'Sign in',
                    'auth.registerTitle': 'Create account',
                    'auth.createdNotice': 'Account created. Sign in.',
                })[key] ?? key,
            },
            stubs: {
                Alert: { template: '<div role="alert"><slot /></div>' },
                Button: { template: '<button v-bind="$attrs"><slot /></button>' },
                Card: { template: '<div><slot /></div>' },
                Input: InputStub,
                'router-link': { template: '<a><slot /></a>' },
            },
        },
    });

    return { wrapper, router };
}

describe('authentication form behavior', () => {
    beforeEach(() => post.mockReset());

    it('submits login credentials and follows the requested redirect', async () => {
        post.mockResolvedValue({});
        const { wrapper, router } = mountAuth('login');
        await wrapper.find('#email').setValue('author@example.test');
        await wrapper.find('#password').setValue('long-test-password');
        await wrapper.vm.submit();

        expect(post).toHaveBeenCalledWith('/api/auth/login', {
            email: 'author@example.test',
            password: 'long-test-password',
        });
        expect(router.push).toHaveBeenCalledWith('/private-area');
        expect(wrapper.find('[role="alert"]').exists()).toBe(false);
    });

    it('reports successful registration without navigating away', async () => {
        post.mockResolvedValue({});
        const { wrapper, router } = mountAuth('register');
        await wrapper.find('#name').setValue('Test Author');
        await wrapper.find('#email').setValue('author@example.test');
        await wrapper.find('#password').setValue('long-test-password');
        await wrapper.vm.submit();

        expect(post).toHaveBeenCalledWith('/api/auth/register', {
            name: 'Test Author',
            email: 'author@example.test',
            password: 'long-test-password',
        });
        expect(wrapper.get('[role="status"]').text()).toContain('Account created. Sign in.');
        expect(router.push).not.toHaveBeenCalled();
    });

    it('shows failed login and falls back to the private posts route', async () => {
        post.mockRejectedValueOnce(new Error('invalid credentials')).mockResolvedValueOnce({});
        const failed = mountAuth('login');
        await failed.wrapper.vm.submit();
        expect(failed.wrapper.get('[role="alert"]').text()).toBe('invalid credentials');

        const fallback = mountAuth('login');
        fallback.wrapper.vm.$route.query.redirect = '';
        await fallback.wrapper.vm.submit();
        expect(fallback.router.push).toHaveBeenCalledWith('/my-posts');
    });
});
