import Mithril from 'mithril';
import Alert, { AlertAttrs } from '../components/Alert';
/**
 * Returned by `AlertManagerState.show`. Used to dismiss alerts.
 */
export declare type AlertIdentifier = number;
export interface AlertState {
    componentClass: typeof Alert;
    attrs: AlertAttrs;
    children: Mithril.Children;
}
export default class AlertManagerState {
    protected activeAlerts: {
        [id: number]: AlertState;
    };
    protected alertId: number;
    getActiveAlerts(): {
        [id: number]: AlertState;
    };
    /**
     * Show an Alert in the alerts area.
     *
     * @returns The alert's ID, which can be used to dismiss the alert.
     */
    show(children: Mithril.Children): AlertIdentifier;
    show(attrs: AlertAttrs, children: Mithril.Children): AlertIdentifier;
    show(componentClass: Alert, attrs: AlertAttrs, children: Mithril.Children): AlertIdentifier;
    /**
     * Dismiss an alert.
     */
    dismiss(key: AlertIdentifier): void;
    /**
     * Clear all alerts.
     */
    clear(): void;
}
