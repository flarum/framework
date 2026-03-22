import DefaultResolver from '../../common/resolvers/DefaultResolver';
import UserPage, { IUserPageAttrs } from '../components/UserPage';
import type Mithril from 'mithril';
export default class UserPageResolver<Attrs extends IUserPageAttrs = IUserPageAttrs, RouteArgs extends Record<string, unknown> = {}> extends DefaultResolver<Attrs, UserPage<Attrs>, RouteArgs> {
    canonicalizeUserSlug(slug: string | undefined): string | undefined;
    makeKey(): string;
    render(vnode: Mithril.Vnode<Attrs, UserPage<Attrs>>): Mithril.Children;
}
