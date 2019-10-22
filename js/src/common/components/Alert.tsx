import Component, {ComponentProps} from '../Component';
import Button from './Button';
import listItems from '../helpers/listItems';
import extract from '../utils/extract';
import * as Mithril from "mithril";

export interface AlertProps extends ComponentProps {
  controls?: Mithril.ChildArray,
  type?: string,
  dismissible?: boolean,

  ondismiss?: () => any,
}

/**
 * The `Alert` component represents an alert box, which contains a message,
 * some controls, and may be dismissible.
 *
 * The alert may have the following special props:
 *
 * - `type` The type of alert this is. Will be used to give the alert a class
 *   name of `Alert--{type}`.
 * - `controls` An array of controls to show in the alert.
 * - `dismissible` Whether or not the alert can be dismissed.
 * - `ondismiss` A callback to run when the alert is dismissed.
 *
 * All other props will be assigned as attributes on the alert element.
 */
export default class Alert extends Component<AlertProps> {
  view() {
    const attrs: AlertProps = Object.assign({}, this.props);

    const type: string = extract(attrs, 'type');
    attrs.className = `Alert Alert--${type} ${attrs.className || ''}`;

    const children: Mithril.Children = extract(attrs, 'children');
    const controls: Mithril.ChildArray = extract(attrs, 'controls') || [];

    // If the alert is meant to be dismissible (which is the case by default),
    // then we will create a dismiss button to append as the final control in
    // the alert.
    const dismissible: boolean|undefined = extract(attrs, 'dismissible');
    const ondismiss: () => any = extract(attrs, 'ondismiss');
    const dismissControl = [];

    if (dismissible || dismissible === undefined) {
      dismissControl.push(
        <Button
          icon="fas fa-times"
          className="Button Button--link Button--icon Alert-dismiss"
          onclick={ondismiss}/>
      );
    }

    return (
      <div {...attrs}>
        <span className="Alert-body">
          {children}
        </span>
        <ul className="Alert-controls">
          {listItems(controls.concat(dismissControl))}
        </ul>
      </div>
    );
  }
}
