import Home from './pages/Home.js';
import NotFound from './pages/NotFound.js';
import Blog from './pages/Blog.js';
import PublishedPost from './pages/PublishedPost.js';
import MyPosts from './pages/MyPosts.js';
import Auth from './pages/Auth.js';

const routes = [
    { path: '/', component: Home },
    { path: '/login', component: Auth, meta: { authMode: 'login' } },
    { path: '/register', component: Auth, meta: { authMode: 'register' } },
    { path: '/blog', component: Blog },
    { path: '/blog/:slug', component: PublishedPost },
    { path: '/my-posts', component: MyPosts },
    { path: '/:pathMatch(.*)*', component: NotFound }
];

export const router = VueRouter.createRouter({
    history: VueRouter.createWebHistory(),
    routes
});

export default router;
