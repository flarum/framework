import type Mithril from 'mithril';
import Alert, { AlertAttrs } from '../components/Alert';
/**
 * Returned by `AlertManagerState.show`. Used to dismiss alerts.
 */
export declare type AlertIdentifier = number;
export declare type AlertArray = {
    [id: AlertIdentifier]: AlertState;
};
export interface AlertState {
    componentClass: typeof Alert;
    attrs: AlertAttrs;
    children: Mithril.Children;
}
export default class AlertManagerState {
    protected activeAlerts: AlertArray;
    protected alertId: AlertIdentifier;
    getActiveAlerts(): AlertArray;
    /**
     * Show an Alert in the alerts area.
     *
     * @return The alert's ID, which can be used to dismiss the alert.
     */
    show(children: Mithril.Children): AlertIdentifier;
    show(attrs: AlertAttrs, children: Mithril.Children): AlertIdentifier;
    show(componentClass: typeof Alert, attrs: AlertAttrs, children: Mithril.Children): AlertIdentifier;
    /**
     * Dismiss an alert.
     */
    dismiss(key: AlertIdentifier | null): void;
    /**
     * Clear all alerts.
     */
    clear(): void;
}
