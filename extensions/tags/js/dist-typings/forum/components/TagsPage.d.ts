import Page from 'flarum/common/components/Page';
import type { IPageAttrs } from 'flarum/common/components/Page';
import ItemList from 'flarum/common/utils/ItemList';
import Mithril from 'mithril';
import type Tag from '../../common/models/Tag';
export interface ITagsPageAttrs extends IPageAttrs {
}
export default class TagsPage<CustomAttrs extends ITagsPageAttrs = ITagsPageAttrs> extends Page<CustomAttrs> {
    private tags;
    private loading;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    view(): JSX.Element;
    contentItems(): ItemList<unknown>;
    hero(): JSX.Element;
    sidebar(): JSX.Element;
    tagTileListView(pinned: Tag[]): JSX.Element;
    tagTileView(tag: Tag): JSX.Element;
    cloudView(cloud: Tag[]): JSX.Element;
}
