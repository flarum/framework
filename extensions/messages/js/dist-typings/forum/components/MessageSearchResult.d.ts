import Component, { type ComponentAttrs } from 'flarum/common/Component';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type DialogMessage from '../../common/models/DialogMessage';
export interface IMessageSearchResultAttrs extends ComponentAttrs {
    message: DialogMessage;
    query: string;
}
/**
 * A private message found by the global search: who wrote it, which
 * conversation it is in, and the part of it that matched.
 */
export default class MessageSearchResult<CustomAttrs extends IMessageSearchResultAttrs = IMessageSearchResultAttrs> extends Component<CustomAttrs> {
    view(): Mithril.Children;
    contentItems(): ItemList<Mithril.Children>;
    /** The query's words, each found wherever it appears, as core highlights post excerpts. */
    highlightRegExp(): RegExp;
}
