import * as Mithril from 'mithril';

import Component, { ComponentProps } from '../Component';
import Button from './Button';
import listItems from '../helpers/listItems';
import extract from '../utils/extract';
import AlertState from '../states/AlertState';

export interface AlertData extends ComponentProps {
    /**
     * An array of controls to show in the alert.
     */
    controls?: Mithril.ChildArray;

    /**
     * The type of alert this is. Will be used to give the alert a class
     *   name of `Alert--{type}`.
     */
    type?: string;

    /**
     * Whether or not the alert can be dismissed.
     */
    dismissible?: boolean;

    /**
     * A callback to run when the alert is dismissed.
     */
    ondismiss?: () => any;
}

export interface AlertProps extends AlertData {
    state: AlertState;
}

/**
 * The `Alert` component represents an alert box, which contains a message,
 * some controls, and may be dismissible.
 *
 * All other props will be assigned as attributes on the alert element.
 */
export default class Alert extends Component<AlertProps> {
    view() {
        const data = this.props.state?.data || this.props;
        const attrs: AlertData = Object.assign({}, data);

        const type: string = extract(attrs, 'type');
        attrs.className = `Alert Alert--${type} ${attrs.className || ''}`;

        const children: Mithril.Children = extract(attrs, 'children');
        const controls: Mithril.ChildArray = extract(attrs, 'controls') || [];

        // If the alert is meant to be dismissible (which is the case by default),
        // then we will create a dismiss button to append as the final control in
        // the alert.
        const dismissible: boolean | undefined = extract(attrs, 'dismissible');
        const ondismiss: () => any = extract(attrs, 'ondismiss');
        const dismissControl: JSX.Element[] = [];

        if (dismissible || dismissible === undefined) {
            dismissControl.push(<Button icon="fas fa-times" className="Button Button--link Button--icon Alert-dismiss" onclick={ondismiss} />);
        }

        return (
            <div {...attrs}>
                <span className="Alert-body">{children}</span>
                <ul className="Alert-controls">{listItems(controls.concat(dismissControl))}</ul>
            </div>
        );
    }
}
