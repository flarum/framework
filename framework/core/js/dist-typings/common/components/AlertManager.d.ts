import Component, { ComponentAttrs } from '../Component';
import AlertManagerState from '../states/AlertManagerState';
import type Mithril from 'mithril';
export interface IAlertManagerAttrs extends ComponentAttrs {
    state: AlertManagerState;
}
/**
 * The `AlertManager` component provides an area in which `Alert` components can
 * be shown and dismissed.
 */
export default class AlertManager<CustomAttrs extends IAlertManagerAttrs = IAlertManagerAttrs> extends Component<CustomAttrs, AlertManagerState> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
}
