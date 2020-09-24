import Alert from '../components/Alert';

export default class AlertManagerState {
  constructor() {
    this.activeAlerts = {};
    this.alertId = 0;
  }

  getActiveAlerts() {
    return this.activeAlerts;
  }

  /**
   * Show an Alert in the alerts area.
   */
  show(arg1, arg2, arg3) {
    let componentClass = Alert;
    let attrs = {};
    let children;
    if (arguments.length == 1) {
      children = arg1;
    } else if (arguments.length == 2) {
      attrs = arg1;
      children = arg2;
    } else if (arguments.length == 3) {
      componentClass = arg1;
      attrs = arg2;
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
  dismiss(key) {
    if (!key || !(key in this.activeAlerts)) return;

    delete this.activeAlerts[key];
    m.redraw();
  }

  /**
   * Clear all alerts.
   *
   * @public
   */
  clear() {
    this.activeAlerts = {};
    m.redraw();
  }
}
