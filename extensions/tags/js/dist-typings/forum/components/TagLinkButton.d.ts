import LinkButton from 'flarum/common/components/LinkButton';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type Tag from '../../common/models/Tag';
export default class TagLinkButton extends LinkButton {
    view(vnode: Mithril.Vnode<any, this>): JSX.Element;
    /**
     * Build the contents of the tag link. Exposed as an ItemList so extensions can
     * add to it (e.g. a realtime typing dot) without overriding the whole view.
     */
    linkItems(tag?: Tag): ItemList<Mithril.Children>;
    static initAttrs(attrs: any): void;
}
