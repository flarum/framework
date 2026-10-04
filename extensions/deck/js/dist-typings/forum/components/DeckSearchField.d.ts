import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type { DeckColumnConfig, DeckColumnSearch } from '../columns/DeckColumnType';
export interface IDeckSearchFieldAttrs extends ComponentAttrs {
    id?: string;
    search: DeckColumnSearch;
    placeholder?: string;
    onpick: (params: DeckColumnConfig['params'] | null) => void;
}
/**
 * A text input that searches as the member types and makes them pick a result,
 * so columns are always built from something that exists (a real user, a real
 * discussion) rather than a typed username or a pasted id.
 */
export default class DeckSearchField<CustomAttrs extends IDeckSearchFieldAttrs = IDeckSearchFieldAttrs> extends Component<CustomAttrs> {
    protected query: string;
    protected results: unknown[];
    protected loading: boolean;
    protected timer: number | null;
    /** Ignores responses to queries the member has since typed past. */
    protected latest: number;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    view(): JSX.Element;
    protected input(value: string): void;
    protected pick(result: unknown): void;
}
