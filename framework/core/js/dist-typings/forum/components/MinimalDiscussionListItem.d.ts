import DiscussionListItem, { IDiscussionListItemAttrs } from './DiscussionListItem';
import ItemList from '../../common/utils/ItemList';
import Mithril from 'mithril';
import type User from '../../common/models/User';
export default class MinimalDiscussionListItem extends DiscussionListItem<IDiscussionListItemAttrs> {
    elementAttrs(): {
        className: string;
    };
    viewItems(): ItemList<Mithril.Children>;
    contentItems(): ItemList<Mithril.Children>;
    authorItems(): ItemList<Mithril.Children>;
    authorTooltipText(user: User | null | false): Mithril.Children;
    mainView(): Mithril.Children;
}
