import IndexPage from './components/IndexPage';
import DiscussionPage from './components/DiscussionPage';
import PostsUserPage from './components/PostsUserPage';
import DiscussionsUserPage from './components/DiscussionsUserPage';
import SettingsPage from './components/SettingsPage';

import Discussion from '../common/models/Discussion';
import Post from '../common/models/Post';
import User from '../common/models/User';

export default app => {
    app.routes = {
        index: { path: '/all', component: IndexPage },

        discussion: { path: '/d/:id', component: DiscussionPage },
        'discussion.near': { path: '/d/:id/:near', component: DiscussionPage },

        user: { path: '/u/:username', component: PostsUserPage },
        'user.posts': { path: '/u/:username', component: PostsUserPage },
        'user.discussions': { path: '/u/:username/discussions', component: DiscussionsUserPage },

        settings: { path: '/settings', component: SettingsPage },

        'index.filter': { path: '/:filter', component: IndexPage },
    };

    /**
     * Generate a URL to a discussion.
     */
    app.route.discussion = (discussion: Discussion, near?: number): string => {
        const slug = discussion?.slug();
        const hasNear = near && near !== 1;
        const params = {
            id: discussion.id() + (slug.trim() ? '-' + slug : ''),
        };

        if (hasNear) params['near'] = near;

        return app.route(hasNear ? 'discussion.near' : 'discussion', params);
    };

    /**
     * Generate a URL to a post.
     */
    app.route.post = (post: Post): string => {
        return app.route.discussion(post.discussion(), post.number());
    };

    /**
     * Generate a URL to a user.
     */
    app.route.user = (user: User): string => {
        return app.route('user', {
            username: user.username(),
        });
    };
};
