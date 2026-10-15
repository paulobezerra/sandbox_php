import Home from './pages/Home.js';
import About from './pages/About.js';
import NotFound from './pages/NotFound.js';
import Blog from './pages/Blog.js';
import PublishedPost from './pages/PublishedPost.js';
import PublishedPage from './pages/PublishedPage.js';
import AdminContent from './pages/AdminContent.js';
import Features from './pages/Features.js';
import Architecture from './pages/Architecture.js';
import Guide from './pages/Guide.js';
import Auth from './pages/Auth.js';
import RecoveryCodes from './pages/RecoveryCodes.js';

const routes = [
    { path: '/', component: Home },
    { path: '/about', component: About },
    { path: '/features', component: Features },
    { path: '/architecture', component: Architecture },
    { path: '/guide', component: Guide },
    { path: '/login', component: Auth, meta: { authMode: 'login' } },
    { path: '/register', component: Auth, meta: { authMode: 'register' } },
    { path: '/forgot-password', component: Auth, meta: { authMode: 'forgot' } },
    { path: '/reset-password', component: Auth, meta: { authMode: 'reset' } },
    { path: '/blog', component: Blog },
    { path: '/blog/:slug', component: PublishedPost },
    { path: '/admin/content', component: AdminContent },
    { path: '/account/recovery-codes', component: RecoveryCodes },
    { path: '/:slug', component: PublishedPage },
    { path: '/:pathMatch(.*)*', component: NotFound }
];

export const router = VueRouter.createRouter({
    history: VueRouter.createWebHistory(),
    routes
});

export default router;
