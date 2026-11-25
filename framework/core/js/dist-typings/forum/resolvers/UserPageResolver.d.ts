import DefaultResolver from '../../common/resolvers/DefaultResolver';
import UserPage, { IUserPageAttrs } from '../components/UserPage';
import type Mithril from 'mithril';
export default class UserPageResolver<Attrs extends IUserPageAttrs = IUserPageAttrs, RouteArgs extends Record<string, unknown> = {}> extends DefaultResolver<Attrs, UserPage<Attrs>, RouteArgs> {
    canonicalizeUserSlug(slug: string | undefined): string | undefined;
    render(vnode: Mithril.Vnode<Attrs, UserPage<Attrs>>): Mithril.Children;
    /**
     * Only for the user `UserPage.loadUser` paired with this address: the client
     * cannot tell which user an address means under the active slug driver.
     */
    protected correctAddress(): void;
}
