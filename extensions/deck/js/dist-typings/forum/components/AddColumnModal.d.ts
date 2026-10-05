import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type DeckState from '../states/DeckState';
import type { DeckColumnConfig, DeckColumnField, DeckColumnType } from '../columns/DeckColumnType';
export interface IAddColumnModalAttrs extends IFormModalAttrs {
    deck: DeckState;
    onadd?: (config: DeckColumnConfig) => void;
}
export default class AddColumnModal<CustomAttrs extends IAddColumnModalAttrs = IAddColumnModalAttrs> extends FormModal<CustomAttrs> {
    protected type: string | null;
    protected values: Record<string, string>;
    /** The chosen type's fields, built once when it's chosen. */
    protected typeFields: DeckColumnField[];
    /** Params from search fields, by field key; null until something is picked. */
    protected picked: Record<string, DeckColumnConfig['params'] | null>;
    /** What each search field shows for its choice. */
    protected chosen: Record<string, Mithril.Children>;
    protected error: Mithril.Children;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    typeItems(): ItemList<Mithril.Children>;
    fields(): ItemList<Mithril.Children>;
    protected input(field: DeckColumnField): Mithril.Children;
    protected choose(key: string, type: DeckColumnType): void;
    /** Filters are written in the search modal, which suggests them as they're typed. */
    protected openFilters(field: DeckColumnField): Promise<void>;
    protected openPicker(field: DeckColumnField): Promise<void>;
    protected back(): void;
    onsubmit(e: SubmitEvent): void;
    protected add(params: DeckColumnConfig['params']): void;
}
