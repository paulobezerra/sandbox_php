import { mount, flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import App from '../src/spa/App.vue';
import Blog from '../src/spa/pages/Blog.vue';
import Home from '../src/spa/pages/Home.vue';
import MyPosts from '../src/spa/pages/MyPosts.vue';
import NotFound from '../src/spa/pages/NotFound.vue';
import PublishedPost from '../src/spa/pages/PublishedPost.vue';

const { api } = vi.hoisted(() => ({ api: {
    get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn(), clearCsrf: vi.fn(),
} }));
vi.mock('../src/api/api.js', () => ({ api }));

const translations = new Proxy({
    'post.pageLabel': 'Page {page} of {pages}',
    'post.deleteConfirm': 'Delete {title}?',
    'home.featureList': 'feature one|feature two',
    'home.architecturePaths': 'one|two',
    'home.authList': 'one|two',
    'home.routesList': 'one|two',
}, { get: (values, key) => values[key] ?? String(key) });

function mountPage(component, options = {}) {
    return mount(component, {
        global: {
            mocks: {
                $t: (key) => translations[key] ?? key,
                $route: { params: { slug: 'hello-world' }, path: '/blog' },
                $router: { push: vi.fn() },
            },
            stubs: {
                'router-link': { props: ['to'], template: '<a :href="to"><slot /></a>' },
                'router-view': true,
            },
        },
        ...options,
    });
}

beforeEach(() => {
    vi.clearAllMocks();
    window.confirm = vi.fn(() => true);
    window.alert = vi.fn();
    window.scrollTo = vi.fn();
});

describe('blog page', () => {
    it('renders loaded posts and moves through pages', async () => {
        api.get.mockResolvedValueOnce({ items: [{ id: 1, title: 'Hello', slug: 'hello', content: 'body', publishedAt: '2026-01-01' }], page: 1, total: 20 });
        const wrapper = mountPage(Blog);
        await flushPromises();
        expect(wrapper.text()).toContain('Hello');
        expect(wrapper.text()).toContain('body');
        expect(wrapper.text()).toContain('Page 1 of 2');

        api.get.mockResolvedValueOnce({ items: [], page: 2, total: 20 });
        await wrapper.get('nav button:last-child').trigger('click');
        await flushPromises();
        expect(api.get).toHaveBeenLastCalledWith('/api/posts?page=2&perPage=10');
        expect(wrapper.text()).toContain('post.emptyPublished');
    });

    it('shows loading, empty, and failed states', async () => {
        api.get.mockReturnValueOnce(new Promise(() => {}));
        const loading = mountPage(Blog);
        expect(loading.get('[role="status"]').exists()).toBe(true);

        api.get.mockResolvedValueOnce({ items: [], page: 1, total: 0 });
        const empty = mountPage(Blog);
        await flushPromises();
        expect(empty.text()).toContain('post.emptyPublished');

        api.get.mockRejectedValueOnce(new Error('offline'));
        const failed = mountPage(Blog);
        await flushPromises();
        expect(failed.get('[role="alert"]').text()).toBe('offline');
    });
});

describe('published post page', () => {
    it('loads a post with and without an excerpt', async () => {
        api.get.mockResolvedValueOnce({ post: { title: 'Article', excerpt: 'Summary', html: '<p>Safe html</p>' } });
        const withExcerpt = mountPage(PublishedPost);
        await flushPromises();
        expect(api.get).toHaveBeenCalledWith('/api/posts/hello-world');
        expect(withExcerpt.text()).toContain('Summary');

        api.get.mockResolvedValueOnce({ post: { title: 'No summary', excerpt: '', html: '<p>Body</p>' } });
        const withoutExcerpt = mountPage(PublishedPost);
        await flushPromises();
        expect(withoutExcerpt.text()).not.toContain('Summary');
        expect(withoutExcerpt.html()).toContain('<p>Body</p>');
    });

    it('shows loading and request errors', async () => {
        api.get.mockReturnValueOnce(new Promise(() => {}));
        expect(mountPage(PublishedPost).get('[role="status"]').exists()).toBe(true);

        api.get.mockRejectedValueOnce(new Error('missing'));
        const failed = mountPage(PublishedPost);
        await flushPromises();
        expect(failed.get('[role="alert"]').text()).toBe('missing');
    });
});

describe('post management page', () => {
    it('creates a post, previews markdown, and reports failed saves', async () => {
        api.get.mockResolvedValue({ items: [], page: 1, total: 0 });
        api.post.mockResolvedValueOnce({}).mockResolvedValueOnce({ html: '<p>preview</p>' }).mockRejectedValueOnce(new Error('save failed'));
        const wrapper = mountPage(MyPosts);
        await flushPromises();
        await wrapper.vm.save();
        expect(api.post).toHaveBeenCalledWith('/api/me/posts', wrapper.vm.form);
        expect(wrapper.text()).toContain('post.savedNotice');

        wrapper.vm.form.content = '# Preview';
        await wrapper.vm.togglePreview();
        expect(wrapper.html()).toContain('<p>preview</p>');
        await wrapper.vm.togglePreview();
        expect(wrapper.vm.preview).toBe(false);

        await wrapper.vm.save();
        expect(wrapper.get('[role="alert"]').text()).toBe('save failed');
    });

    it('edits and updates a post, toggles publication, and deletes only when confirmed', async () => {
        api.get.mockResolvedValue({ items: [], page: 1, total: 0 });
        api.put.mockResolvedValue({});
        api.post.mockResolvedValue({});
        api.delete.mockResolvedValue({});
        const wrapper = mountPage(MyPosts);
        await flushPromises();

        const post = { id: 7, title: 'Draft', slug: 'draft', excerpt: null, content: 'text', status: 'DRAFT' };
        wrapper.vm.edit(post);
        expect(wrapper.vm.form.excerpt).toBe('');
        await wrapper.vm.save();
        expect(api.put).toHaveBeenCalledWith('/api/me/posts/7', {
            title: 'Draft', slug: 'draft', excerpt: '', content: 'text',
        });
        expect(wrapper.vm.editingId).toBeNull();

        await wrapper.vm.publish(post);
        expect(api.post).toHaveBeenCalledWith('/api/me/posts/7/publish');
        await wrapper.vm.publish({ ...post, status: 'PUBLISHED' });
        expect(api.post).toHaveBeenLastCalledWith('/api/me/posts/7/unpublish');

        window.confirm.mockReturnValueOnce(false);
        await wrapper.vm.remove(post);
        expect(api.delete).not.toHaveBeenCalled();
        await wrapper.vm.remove(post);
        expect(api.delete).toHaveBeenCalledWith('/api/me/posts/7');
        expect(window.confirm).toHaveBeenCalledWith('Delete Draft?');
    });

    it('handles preview, publish, delete, and list request failures', async () => {
        api.get.mockRejectedValueOnce(new Error('list failed'));
        const wrapper = mountPage(MyPosts);
        await flushPromises();
        expect(wrapper.get('[role="alert"]').text()).toBe('list failed');

        api.post.mockRejectedValueOnce(new Error('preview failed')).mockRejectedValueOnce(new Error('publish failed'));
        await wrapper.vm.togglePreview();
        expect(wrapper.vm.error).toBe('preview failed');
        await wrapper.vm.publish({ id: 3, status: 'DRAFT' });
        expect(wrapper.vm.error).toBe('publish failed');

        api.delete.mockRejectedValueOnce(new Error('delete failed'));
        await wrapper.vm.remove({ id: 3, title: 'Keep' });
        expect(wrapper.vm.error).toBe('delete failed');
    });

    it('renders saved drafts and published posts with actions and pagination', async () => {
        api.get.mockResolvedValue({
            items: [
                { id: 1, title: 'Published', slug: 'published', status: 'PUBLISHED' },
                { id: 2, title: 'Draft', slug: 'draft', status: 'DRAFT' },
            ],
            page: 1,
            total: 25,
        });
        api.post.mockResolvedValue({});
        api.delete.mockResolvedValue({});
        const wrapper = mountPage(MyPosts);
        await flushPromises();
        expect(wrapper.text()).toContain('post.published');
        expect(wrapper.text()).toContain('post.draft');
        expect(wrapper.text()).toContain('/blog/published');
        expect(wrapper.text()).toContain('Page 1 of 3');

        const rowButtons = wrapper.findAll('tbody button');
        await rowButtons[0].trigger('click');
        expect(wrapper.vm.editingId).toBe(1);
        await wrapper.find('tbody tr:first-child button:nth-child(2)').trigger('click');
        await flushPromises();
        expect(api.post).toHaveBeenCalledWith('/api/me/posts/1/unpublish');
        await wrapper.find('nav button:last-child').trigger('click');
        await flushPromises();
        expect(api.get).toHaveBeenLastCalledWith('/api/me/posts?page=2&perPage=10');
    });
});

describe('shell and static pages', () => {
    it('renders home content and the not-found route', () => {
        const home = mountPage(Home);
        expect(home.text()).toContain('home.title');
        expect(home.text()).toContain('feature one');
        const missing = mountPage(NotFound);
        expect(missing.text()).toContain('notFound.title');
    });

    it('loads guest and authenticated shell states, reacts to events, and logs out', async () => {
        api.get.mockResolvedValueOnce({ user: null }).mockResolvedValueOnce({ user: { id: 4, name: 'Author' } });
        api.post.mockResolvedValueOnce({}).mockRejectedValueOnce(new Error('logout failed'));
        const router = { push: vi.fn() };
        const guest = mountPage(App, { global: { mocks: { $t: (key) => key, $route: { path: '/my-posts' }, $router: router }, stubs: { 'router-view': true, 'router-link': { template: '<a><slot /></a>' } } } });
        await flushPromises();
        expect(guest.text()).toContain('shell.login');
        await guest.get('.sidebar-toggle').trigger('click');
        expect(guest.get('.sidebar-toggle').attributes('aria-expanded')).toBe('true');

        const signedIn = mountPage(App, { global: { mocks: { $t: (key) => key, $route: { path: '/my-posts' }, $router: router }, stubs: { 'router-view': true, 'router-link': { template: '<a><slot /></a>' } } } });
        await flushPromises();
        expect(signedIn.text()).toContain('shell.myPosts');
        await signedIn.vm.logout();
        expect(api.clearCsrf).toHaveBeenCalled();
        expect(router.push).toHaveBeenCalledWith('/');
        await signedIn.vm.logout();
        expect(window.alert).toHaveBeenCalledWith('logout failed');
    });

    it('keeps guests signed out after a failed session lookup and removes its listener', async () => {
        api.get.mockRejectedValueOnce(new Error('unauthenticated'));
        const wrapper = mountPage(App);
        await flushPromises();
        expect(wrapper.text()).toContain('shell.login');
        const removeListener = vi.spyOn(window, 'removeEventListener');
        wrapper.unmount();
        expect(removeListener).toHaveBeenCalledWith('auth-changed', expect.any(Function));

        api.get.mockResolvedValueOnce({ user: null });
        api.post.mockResolvedValueOnce({});
        const router = { push: vi.fn() };
        const notOnPrivateRoute = mountPage(App, { global: { mocks: { $t: (key) => key, $route: { path: '/blog' }, $router: router }, stubs: { 'router-view': true } } });
        await flushPromises();
        await notOnPrivateRoute.vm.logout();
        expect(api.clearCsrf).toHaveBeenCalled();
        expect(router.push).not.toHaveBeenCalled();
    });
});
