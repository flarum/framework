import Page, { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
/**
 * The `NotificationsPage` component shows the notifications list. It is only
 * used on mobile devices where the notifications dropdown is within the drawer.
 */
export default class NotificationsPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends Page<CustomAttrs> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
}
