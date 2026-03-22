import app from '../../forum/app';
import DefaultResolver from '../../common/resolvers/DefaultResolver';
import UserPage, { IUserPageAttrs } from '../components/UserPage';

import type User from '../../common/models/User';
import type Mithril from 'mithril';

export default class UserPageResolver<
  Attrs extends IUserPageAttrs = IUserPageAttrs,
  RouteArgs extends Record<string, unknown> = {}
> extends DefaultResolver<Attrs, UserPage<Attrs>, RouteArgs> {
  canonicalizeUserSlug(slug: string | undefined) {
    if (!slug) return;
    return slug.split('-')[0];
  }

  makeKey() {
    const params = { ...m.route.param() };
    params.username = this.canonicalizeUserSlug(params.username);
    return this.routeName + JSON.stringify(params);
  }

  render(vnode: Mithril.Vnode<Attrs, UserPage<Attrs>>) {
    const currentSlug = m.route.param('username');
    const id = this.canonicalizeUserSlug(currentSlug);

    if (id) {
      const user = app.store.getById<User>('users', id);

      if (user && user.slug() && currentSlug !== user.slug()) {
        window.history.replaceState(null, '', app.route(this.routeName, { username: user.slug() }));
      }
    }

    return super.render(vnode);
  }
}
