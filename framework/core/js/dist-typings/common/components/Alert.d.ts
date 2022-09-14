import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
export interface AlertAttrs extends ComponentAttrs {
    /** The type of alert this is. Will be used to give the alert a class name of `Alert--{type}`. */
    type?: string;
    /** Title of the alert. Optional. */
    title?: Mithril.Children;
    /** Icon used next to the title. Optional. */
    icon?: string;
    /** An array of controls to show in the alert. */
    controls?: Mithril.Children;
    /** Whether or not the alert can be dismissed. */
    dismissible?: boolean;
    /** A callback to run when the alert is dismissed */
    ondismiss?: Function;
}
/**
 * The `Alert` component represents an alert box, which contains a message,
 * some controls, and may be dismissible.
 */
export default class Alert<T extends AlertAttrs = AlertAttrs> extends Component<T> {
    view(vnode: Mithril.VnodeDOM<T, this>): JSX.Element;
}
