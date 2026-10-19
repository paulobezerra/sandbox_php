import { createRouter, createWebHistory } from 'vue-router';
import Home from './pages/Home.vue';
import NotFound from './pages/NotFound.vue';
import Blog from './pages/Blog.vue';
import PublishedPost from './pages/PublishedPost.vue';
import MyPosts from './pages/MyPosts.vue';
import Auth from './pages/Auth.vue';

const routes = [
    { path: '/', component: Home },
    { path: '/login', component: Auth, meta: { authMode: 'login' } },
    { path: '/register', component: Auth, meta: { authMode: 'register' } },
    { path: '/blog', component: Blog },
    { path: '/blog/:slug', component: PublishedPost },
    { path: '/my-posts', component: MyPosts },
    { path: '/:pathMatch(.*)*', component: NotFound }
];

export const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
