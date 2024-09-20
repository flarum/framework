import type Mithril from 'mithril';
import AutocompleteDropdown, { type AutocompleteDropdownAttrs } from './AutocompleteDropdown';
import GambitsAutocomplete from '../utils/GambitsAutocomplete';
export interface GambitsAutocompleteDropdownAttrs extends AutocompleteDropdownAttrs {
    resource: string;
}
/**
 * This is an autocomplete component not related to the SearchModal forum components.
 * It is a standalone component that can be reused for search inputs of any other types
 * of resources. It will display a dropdown menu under the input with gambit suggestions
 * similar to the SearchModal component.
 */
export default class GambitsAutocompleteDropdown<CustomAttrs extends GambitsAutocompleteDropdownAttrs = GambitsAutocompleteDropdownAttrs> extends AutocompleteDropdown<CustomAttrs> {
    protected gambitsAutocomplete: GambitsAutocomplete;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    suggestions(): JSX.Element[];
}
