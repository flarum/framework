import Component, { ComponentAttrs } from '../Component';
import Button from './Button';
import listItems from '../helpers/listItems';
import extract from '../utils/extract';
import Mithril from 'mithril';

export interface AlertAttrs extends ComponentAttrs {
  /** The type of alert this is. Will be used to give the alert a class name of `Alert--{type}`. */
  type?: string;
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
  view(vnode: Mithril.Vnode) {
    const attrs = Object.assign({}, this.attrs);

    const type = extract(attrs, 'type');
    attrs.className = 'Alert Alert--' + type + ' ' + (attrs.className || '');

    const content = extract(attrs, 'content') || vnode.children;
    const controls = (extract(attrs, 'controls') || []) as Mithril.ChildArray;

    // If the alert is meant to be dismissible (which is the case by default),
    // then we will create a dismiss button to append as the final control in
    // the alert.
    const dismissible = extract(attrs, 'dismissible');
    const ondismiss = extract(attrs, 'ondismiss');
    const dismissControl = [];

    if (dismissible || dismissible === undefined) {
      dismissControl.push(<Button icon="fas fa-times" className="Button Button--link Button--icon Alert-dismiss" onclick={ondismiss} />);
    }

    return (
      <div {...attrs}>
        <span className="Alert-body">{content}</span>
        <ul className="Alert-controls">{listItems(controls.concat(dismissControl))}</ul>
      </div>
    );
  }
}
