import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import { type Connection } from '../utils/settings';
import type { ModelLists } from '../utils/models';
export interface EditConnectionModalAttrs extends IFormModalAttrs {
    /** Set when editing; a new connection has none yet. */
    name?: string;
    connection?: Connection;
    takenNames: string[];
    /** The saved connection whose models can be listed, if this is one. */
    listedAs?: string | null;
    lists?: ModelLists;
    onsave: (name: string, connection: Connection) => void;
}
type Field = 'name' | 'driver' | 'url' | 'region' | 'key' | 'model' | 'monthlyTokens' | 'guardrail';
/**
 * Adds or edits a connection on the page. Nothing is stored until the admin
 * saves the page.
 */
export default class EditConnectionModal<CustomAttrs extends EditConnectionModalAttrs = EditConnectionModalAttrs> extends FormModal<CustomAttrs> {
    name: Stream<string>;
    driver: Stream<string>;
    url: Stream<string>;
    region: Stream<string>;
    guardrailId: Stream<string>;
    guardrailVersion: Stream<string>;
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
    /**
     * The providers to choose from: those the page offers, and the one the
     * connection was saved with, so a Bedrock connection can still be changed
     * or removed after the AWS SDK it needs is taken away.
     */
    driverOptions(): Record<string, string>;
    /**
     * Whether the provider is reached by region, not address.
     */
    byRegion(): boolean;
    field(field: Field, label: string, help: string | null, control: Mithril.Children, extra?: Mithril.Children): Mithril.Children;
    /**
     * Whether the models listed for the saved connection are this one's: it
     * still has the provider, address and key it was saved with.
     */
    listsSavedModels(): boolean;
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
