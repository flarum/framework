import type Mithril from 'mithril';
import Alert, { AlertAttrs } from '../components/Alert';
import app from '../app';

/**
 * Returned by `AlertManagerState.show`. Used to dismiss alerts.
 */
export type AlertIdentifier = number;

export type AlertArray = { [id: AlertIdentifier]: AlertState };

export interface AlertState {
  componentClass: typeof Alert;
  attrs: AlertAttrs;
  children: Mithril.Children;
}

export default class AlertManagerState {
  protected activeAlerts: AlertArray = {};
  protected alertId: AlertIdentifier = 0;
  protected loadingPool: number = 0;

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
  protected loadingTimeout: ReturnType<typeof setTimeout> | null = null;

  /**
   * Identifier of the currently-shown loading alert, or null when none is shown.
   */
  protected loadingAlertId: AlertIdentifier | null = null;

  getActiveAlerts() {
    return this.activeAlerts;
  }

  /**
   * Show an Alert in the alerts area.
   *
   * @return The alert's ID, which can be used to dismiss the alert.
   */
  show(children: Mithril.Children): AlertIdentifier;
  show(attrs: AlertAttrs, children: Mithril.Children): AlertIdentifier;
  show(componentClass: typeof Alert, attrs: AlertAttrs, children: Mithril.Children): AlertIdentifier;

  show(arg1: any, arg2?: any, arg3?: any) {
    // Assigns variables as per the above signatures
    let componentClass = Alert;
    let attrs: AlertAttrs = {};
    let children: Mithril.Children;

    if (arguments.length == 1) {
      children = arg1 as Mithril.Children;
    } else if (arguments.length == 2) {
      attrs = arg1 as AlertAttrs;
      children = arg2 as Mithril.Children;
    } else if (arguments.length == 3) {
      componentClass = arg1 as typeof Alert;
      attrs = arg2 as AlertAttrs;
      children = arg3;
    }

    this.activeAlerts[++this.alertId] = { children, attrs, componentClass };
    m.redraw();

    return this.alertId;
  }

  /**
   * Dismiss an alert.
   */
  dismiss(key: AlertIdentifier | null): void {
    if (!key || !(key in this.activeAlerts)) return;

    delete this.activeAlerts[key];
    m.redraw();
  }

  /**
   * Clear all alerts.
   */
  clear(): void {
    this.activeAlerts = {};
    m.redraw();
  }

  /**
   * Register an outstanding load and, if this is the first one, schedule the
   * loading indicator to appear after {@link AlertManagerState.LOADING_DELAY}.
   *
   * Concurrent loads share a single indicator (tracked via `loadingPool`), so the
   * UI never shows more than one "loading" alert regardless of how many chunks or
   * requests are in flight. Loads that finish before the delay never show one.
   */
  showLoading(): void {
    this.loadingPool++;

    // Only the first concurrent load arms the timer; the rest just join the pool.
    if (this.loadingPool > 1 || this.loadingTimeout !== null || this.loadingAlertId !== null) {
      return;
    }

    this.loadingTimeout = setTimeout(() => {
      this.loadingTimeout = null;

      this.loadingAlertId = this.show(
        {
          type: 'warning',
          dismissible: false,
        },
        app.translator.trans('core.lib.loading_indicator.accessible_label')
      );
    }, AlertManagerState.LOADING_DELAY);
  }

  /**
   * Mark one outstanding load as finished. When the last one completes, cancel a
   * still-pending indicator (so a fast load never flickers) and dismiss the
   * indicator if it was already shown.
   */
  clearLoading(): void {
    // Guard against an unbalanced clearLoading() driving the pool negative.
    if (this.loadingPool > 0) {
      this.loadingPool--;
    }

    if (this.loadingPool > 0) return;

    if (this.loadingTimeout !== null) {
      clearTimeout(this.loadingTimeout);
      this.loadingTimeout = null;
    }

    if (this.loadingAlertId !== null) {
      this.dismiss(this.loadingAlertId);
      this.loadingAlertId = null;
    }
  }
}
