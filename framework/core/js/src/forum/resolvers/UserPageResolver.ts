import app from '../../forum/app';
import DefaultResolver from '../../common/resolvers/DefaultResolver';
import UserPage, { IUserPageAttrs } from '../components/UserPage';

import type User from '../../common/models/User';
import type Mithril from 'mithril';

export default class UserPageResolver<
  Attrs extends IUserPageAttrs = IUserPageAttrs,
  RouteArgs extends Record<string, unknown> = {},
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
    this.correctAddress();

    return super.render(vnode);
  }

  /**
   * Only for the user `UserPage.loadUser` paired with this address: the client
   * cannot tell which user an address means under the active slug driver.
   */
  protected correctAddress(): void {
    const slug = m.route.param('username');
    const resolved: { slug: string; user: User } | undefined = app.current.get('resolvedUser');

    if (!slug || resolved?.slug !== slug || app.current.get('routeName') !== this.routeName) return;

    const canonical = resolved.user.slug();

    if (!canonical || canonical === slug) return;

    // Mithril changes the address as soon as a navigation starts, and resolves the
    // new route later: leave an address that is no longer this route's own.
    const address = new URL(app.route(this.routeName, { username: slug }), window.location.href);

    if (address.pathname !== window.location.pathname) return;

    window.history.replaceState(
      window.history.state,
      '',
      app.route(this.routeName, { username: canonical }) + window.location.search + window.location.hash
    );
  }
}
