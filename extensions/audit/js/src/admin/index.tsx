import app from 'flarum/admin/app';
import addForumRoutes from './addForumRoutes';

export { default as extend } from './extend';

app.initializers.add('flarum-audit', () => {
  addForumRoutes();
});
