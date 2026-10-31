import type Mithril from 'mithril';
import Alert, { AlertAttrs } from '../components/Alert';
/**
 * Returned by `AlertManagerState.show`. Used to dismiss alerts.
 */
export type AlertIdentifier = number;
export type AlertArray = {
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
    protected loadingPool: number;
    /**
     * How long (ms) a load must run before the loading indicator is shown. Loads
     * that complete faster than this never show an indicator, avoiding a flicker
     * for fast (e.g. cached) chunk loads.
     */
    protected static readonly LOADING_DELAY = 250;
    /**
     * Pending timer for the delayed display of the loading indicator, or null when
     * no display is pending.
     */
    protected loadingTimeout: ReturnType<typeof setTimeout> | null;
    /**
     * Identifier of the currently-shown loading alert, or null when none is shown.
     */
    protected loadingAlertId: AlertIdentifier | null;
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
    /**
     * Register an outstanding load and, if this is the first one, schedule the
     * loading indicator to appear after {@link AlertManagerState.LOADING_DELAY}.
     *
     * Concurrent loads share a single indicator (tracked via `loadingPool`), so the
     * UI never shows more than one "loading" alert regardless of how many chunks or
     * requests are in flight. Loads that finish before the delay never show one.
     */
    showLoading(): void;
    /**
     * Mark one outstanding load as finished. When the last one completes, cancel a
     * still-pending indicator (so a fast load never flickers) and dismiss the
     * indicator if it was already shown.
     */
    clearLoading(): void;
}
