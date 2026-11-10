import Component, { ComponentAttrs } from '../Component';
import { type FontAwesomeIcon, type FontAwesomeStyle } from '../utils/fontAwesomeIcons';
import type Mithril from 'mithril';
export interface IIconPickerAttrs extends ComponentAttrs {
    /** The class currently chosen, e.g. `fas fa-bolt`. */
    value?: string;
    /**
     * Shown before the search box, such as the button that opened the picker,
     * so the picker can stand in for the field it belongs to.
     */
    toggle?: Mithril.Children;
    /** Called with the full class of the icon picked. */
    onpick: (icon: string) => void;
    /**
     * Called when the picker should close: on Escape, or a click outside it.
     * Only Escape should move focus back to the field.
     */
    onclose: (reason: 'escape' | 'outside') => void;
}
/**
 * A searchable grid of the Font Awesome icons the forum can draw, in solid,
 * regular and brands styles. See `IconInput`, which pairs it with a text field
 * that still accepts any class.
 */
export default class IconPicker<CustomAttrs extends IIconPickerAttrs = IIconPickerAttrs> extends Component<CustomAttrs> {
    query: string;
    /** Whether the forum's stylesheets are still being fetched to read them. */
    reading: boolean;
    styles: FontAwesomeStyle[];
    style?: FontAwesomeStyle;
    /** The icons of each style shown so far, by its class. */
    loaded: Map<string, FontAwesomeIcon[]>;
    closeWatcher?: CloseWatcher;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    readStyles(): void;
    /**
     * The style a value is drawn in, from its short class (`fasl`) or its long
     * ones (`fa-sharp fa-light`, where classic and solid are the defaults).
     */
    styleOf(value: string): FontAwesomeStyle | undefined;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onkeyup: (e: KeyboardEvent) => void;
    onmousedown: (e: MouseEvent) => void;
    setStyle(style: FontAwesomeStyle | undefined): void;
    /**
     * Whether an icon is the current value, however the value is written:
     * `fasl fa-house` and `fa-sharp fa-light fa-house` are the same icon.
     */
    isCurrent(icon: FontAwesomeIcon): boolean;
    /**
     * Show another family, at the weight shown now where the family has it.
     */
    setFamily(family: string): void;
    icons(): FontAwesomeIcon[] | null;
    familyLabel(family: string): string;
    weightLabel(weight: number): string;
    view(): JSX.Element;
    /**
     * Pick for Enter: an icon named exactly, then a typed class nothing matches,
     * then the closest match. Worked out from the search as it is now, as Enter
     * can come before the redraw that follows typing, such as after a paste.
     */
    pickFromSearch(): void;
    /**
     * The words of the search that name an icon: "fas fa-house",
     * "fa-sharp fa-light fa-house" and "fa-house" all search for "house".
     */
    searchWords(): string[];
    /**
     * The search as a class to use as it is, when it is written like one
     * (`fa-kit fa-my-icon`): a Kit's own icons and other custom classes aren't
     * in the list, but can still be chosen.
     */
    typedClass(): string | null;
    /**
     * Search for a typed class in the style it names, so Enter picks
     * `fasl fa-house` in Sharp Light, not in the style shown before.
     */
    search(query: string): void;
    matches(icons: FontAwesomeIcon[] | null): FontAwesomeIcon[];
    footer(count: number): string | any[];
}
