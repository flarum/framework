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
   * Shows a loading alert.
   */
  showLoading(): AlertIdentifier | null {
    this.loadingPool++;

    if (this.loadingPool > 1) return null;

    return this.show(
      {
        type: 'warning',
        dismissible: false,
      },
      app.translator.trans('core.lib.loading_indicator.accessible_label')
    );
  }

  /**
   * Hides a loading alert.
   */
  clearLoading(): void {
    this.loadingPool--;

    if (this.loadingPool === 0) this.clear();
  }
}
