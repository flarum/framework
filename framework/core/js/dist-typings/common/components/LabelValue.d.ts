import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
export interface ILabelValueAttrs extends ComponentAttrs {
    label: Mithril.Children;
    value: Mithril.Children;
}
/**
 * A generic component for displaying a label and value inline.
 * Created to avoid reinventing the wheel.
 *
 * `label: value`
 */
export default class LabelValue<CustomAttrs extends ILabelValueAttrs = ILabelValueAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
}
