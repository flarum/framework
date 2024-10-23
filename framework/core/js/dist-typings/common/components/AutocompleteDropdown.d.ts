import Component, { type ComponentAttrs } from '../Component';
import KeyboardNavigatable from '../utils/KeyboardNavigatable';
import type Mithril from 'mithril';
export interface AutocompleteDropdownAttrs extends ComponentAttrs {
    query: string;
    onchange: (value: string) => void;
}
/**
 * A reusable component that wraps around an input element and displays a list
 * of suggestions based on the input's value.
 * Must be extended and the `suggestions` method implemented.
 */
export default abstract class AutocompleteDropdown<CustomAttrs extends AutocompleteDropdownAttrs = AutocompleteDropdownAttrs> extends Component<CustomAttrs> {
    /**
     * The index of the currently-selected <li> in the results list. This can be
     * a unique string (to account for the fact that an item's position may jump
     * around as new results load), but otherwise it will be numeric (the
     * sequential position within the list).
     */
    protected index: number;
    protected navigator: KeyboardNavigatable;
    private updateMaxHeightHandler?;
    /**
     * Whether the input has focus.
     */
    protected hasFocus: boolean;
    abstract suggestions(): JSX.Element[];
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    updateMaxHeight(): void;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    selectableItems(): JQuery;
    inputElement(): JQuery<HTMLInputElement>;
    selectSuggestion(): void;
    /**
     * Get the position of the currently selected item.
     * Returns zero if not found.
     */
    getCurrentNumericIndex(): number;
    /**
     * Get the <li> in the search results with the given index (numeric or named).
     */
    getItem(index: number): JQuery;
    /**
     * Set the currently-selected search result item to the one with the given
     * index.
     */
    setIndex(index: number, scrollToItem?: boolean): void;
}
