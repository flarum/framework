import Component, { ComponentAttrs } from '../Component';
import type IconImageState from '../states/IconImageState';
import type Mithril from 'mithril';
export interface IIconImageInputAttrs extends ComponentAttrs {
    state: IconImageState;
    id?: string;
}
/**
 * Choose or remove an image to show instead of a model's Font Awesome icon.
 * The change is held in `state` until the form saves it.
 */
export default class IconImageInput<CustomAttrs extends IIconImageInputAttrs = IIconImageInputAttrs> extends Component<CustomAttrs> {
    static ACCEPT: string;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    choose(): void;
}
