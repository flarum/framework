import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import { type Connection } from '../connections';
export interface EditConnectionModalAttrs extends IFormModalAttrs {
    /** Set when editing; a new connection has none yet. */
    name?: string;
    connection?: Connection;
    takenNames: string[];
    onsave: (name: string, connection: Connection) => void;
}
/**
 * Adds or edits a connection on the page. Nothing is stored until the admin
 * saves the page.
 */
export default class EditConnectionModal<CustomAttrs extends EditConnectionModalAttrs = EditConnectionModalAttrs> extends FormModal<CustomAttrs> {
    name: Stream<string>;
    driver: Stream<string>;
    url: Stream<string>;
    model: Stream<string>;
    monthlyTokens: Stream<string>;
    key: Stream<string>;
    removeKey: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    fields(): ItemList<Mithril.Children>;
    keyField(optional: boolean): Mithril.Children;
    /**
     * A saved key only carries over while the provider stays the same: it's no
     * use for another one.
     */
    hasSavedKey(): boolean;
    onsubmit(e: SubmitEvent): void;
}
