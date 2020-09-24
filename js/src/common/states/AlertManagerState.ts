import Mithril from 'mithril';
import Alert, { AlertAttrs } from '../components/Alert';

export interface AlertState {
  componentClass: AlertManagerState;
  attrs: AlertAttrs;
  children: Mithril.Children;
}

export default class AlertManagerState {
  protected activeAlerts: { [ids: string]: AlertState } = {};
  protected alertId = 0;

  public getActiveAlerts() {
    return this.activeAlerts;
  }

  /**
   * Show an Alert in the alerts area.
   *
   * @returns The alert's ID, which can be used to dismiss the alert.
   */
  public show(children: Mithril.Children): number;
  public show(attrs: AlertAttrs, children: Mithril.Children): number;
  public show(componentClass: Alert, attrs: AlertAttrs, children: Mithril.Children): number;

  public show(arg1: Mithril.Children | AlertAttrs | Alert, arg2?: AlertAttrs | Mithril.Children, arg3?: Mithril.Children) {
    let componentClass = Alert;
    let attrs: AlertAttrs = {};
    let children: Mithril.Children;
    if (arguments.length == 1) {
      children = arg1;
    } else if (arguments.length == 2) {
      attrs = arg1;
      children = arg2;
    } else if (arguments.length == 3) {
      componentClass = arg1;
      attrs = arg2 as AlertAttrs;
      children = arg3;
    }

    // Breaking Change Compliance Warning, Remove in Beta 15.
    // This is applied to the first argument (attrs) because previously, the alert was passed as the first argument.
    if (attrs === Alert || attrs instanceof Alert) {
      // This is duplicated so that if the error is caught, an error message still shows up in the debug console.
      console.error('The AlertManager can only show Alerts. Whichever extension triggered this alert should be updated to comply with beta 14.');
      throw new Error('The AlertManager can only show Alerts. Whichever extension triggered this alert should be updated to comply with beta 14.');
    }
    // End Change Compliance Warning, Remove in Beta 15
    this.activeAlerts[++this.alertId] = { children, attrs, componentClass };
    m.redraw();

    return this.alertId;
  }

  /**
   * Dismiss an alert.
   */
  public dismiss(key: number): void {
    if (!key || !(key in this.activeAlerts)) return;

    delete this.activeAlerts[key];
    m.redraw();
  }

  /**
   * Clear all alerts.
   */
  public clear(): void {
    this.activeAlerts = {};
    m.redraw();
  }
}
