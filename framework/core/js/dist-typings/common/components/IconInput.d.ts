import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
export interface IIconInputAttrs extends ComponentAttrs {
    value: string;
    id?: string;
    placeholder?: string;
    onchange?: (event: {
        target: {
            value: string;
        };
    }) => void;
    oninput?: (event: {
        target: {
            value: string;
        };
    }) => void;
}
/**
 * A text field for a Font Awesome icon class, with a preview of the icon that
 * opens an `IconPicker`. Typing any class still works exactly as before — the
 * picker only fills the field — so Pro, Kit and custom icons stay available.
 *
 * Like `ColorPreviewInput`, it accepts `bidi`, so it can stand in for a plain
 * `<input>`.
 */
export default class IconInput<CustomAttrs extends IIconInputAttrs = IIconInputAttrs> extends Component<CustomAttrs> {
    picking: boolean;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    toggle(): void;
    open(): void;
    /**
     * Put focus back on the toggle once the picker — which held it — is gone,
     * so keyboard and screen reader users keep their place. The toggle moves
     * from the picker back to the field, so the field is drawn first.
     */
    refocus(): void;
    /**
     * Report a picked icon the way the field itself would. A `bidi` handler
     * reads `this.value`; any other reads `event.target.value`. This satisfies
     * both.
     */
    change(value: string): void;
}
