import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import { type Connection } from '../utils/settings';
export interface EditConnectionModalAttrs extends IFormModalAttrs {
    /** Set when editing; a new connection has none yet. */
    name?: string;
    connection?: Connection;
    takenNames: string[];
    onsave: (name: string, connection: Connection) => void;
}
type Field = 'name' | 'driver' | 'url' | 'key' | 'model' | 'monthlyTokens';
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
    invalid: Field | null;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    fields(): ItemList<Mithril.Children>;
    field(field: Field, label: string, help: string | null, control: Mithril.Children, extra?: Mithril.Children): Mithril.Children;
    fieldAttrs(field: Field): Record<string, unknown>;
    keyField(optional: boolean): Mithril.Children;
    /**
     * Whether the saved key will still be used: only with the provider and API
     * URL it was saved for, as the server checks.
     */
    savedKeyApplies(): boolean;
    /**
     * The first problem with the form, checked as the server would, so the
     * admin can fix it here, next to the field.
     */
    validate(): {
        field: Field;
        message: string;
    } | null;
    onsubmit(e: SubmitEvent): void;
}
export {};
