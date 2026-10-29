import app from 'flarum/admin/app';
import type { ResourceRoutes } from '../common/routeHelpers';

// When we are in the admin panel, the URL builders for the frontend aren't available,
// so we add them if they are missing.
export default function () {
  const route = app.route as unknown as ResourceRoutes;

  if (!route.discussion) {
    route.discussion = (discussion) => {
      return app.forum.attribute('baseUrl') + '/d/' + discussion.slug();
    };
  }
  if (!route.post) {
    route.post = (post) => {
      return route.discussion(post.discussion()) + '/' + post.number();
    };
  }
  if (!route.tag) {
    route.tag = (tag) => {
      return app.forum.attribute('baseUrl') + '/t/' + tag.slug();
    };
  }
  if (!route.user) {
    route.user = (user) => {
      return app.forum.attribute('baseUrl') + '/u/' + user.slug();
    };
  }
}
