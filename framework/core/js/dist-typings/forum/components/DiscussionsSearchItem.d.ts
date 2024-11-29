import Component, { ComponentAttrs } from '../../common/Component';
import Discussion from '../../common/models/Discussion';
import Post from '../../common/models/Post';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export interface DiscussionsSearchItemAttrs extends ComponentAttrs {
    query: string;
    discussion: Discussion;
    mostRelevantPost: Post;
}
export default class DiscussionsSearchItem extends Component<DiscussionsSearchItemAttrs> {
    query: string;
    discussion: Discussion;
    mostRelevantPost: Post | null | undefined;
    oninit(vnode: Mithril.Vnode<DiscussionsSearchItemAttrs, this>): void;
    view(): JSX.Element;
    discussionTitle(): string;
    mostRelevantPostContent(): string | null | undefined;
    viewItems(): ItemList<Mithril.Children>;
}
