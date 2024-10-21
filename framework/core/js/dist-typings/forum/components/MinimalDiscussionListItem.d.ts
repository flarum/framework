import DiscussionListItem, { IDiscussionListItemAttrs } from './DiscussionListItem';
import ItemList from '../../common/utils/ItemList';
import Mithril from 'mithril';
export default class MinimalDiscussionListItem extends DiscussionListItem<IDiscussionListItemAttrs> {
    elementAttrs(): {
        className: string;
    };
    viewItems(): ItemList<Mithril.Children>;
    contentItems(): ItemList<Mithril.Children>;
    authorItems(): ItemList<Mithril.Children>;
    mainView(): Mithril.Children;
}
