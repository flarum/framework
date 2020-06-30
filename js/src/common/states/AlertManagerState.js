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
  show(attrs, componentClass = Alert) {
    // Breaking Change Compliance Warning, Remove in Beta 15.
    // This is applied to the first argument (attrs) because previously, the alert was passed as the first argument.
    if (attrs === Alert || attrs instanceof Alert) {
      // This is duplicated so that if the error is caught, an error message still shows up in the debug console.
      console.error('The AlertManager can only show Alerts. Whichever extension triggered this alert should be updated to comply with beta 14.');
      throw new Error('The AlertManager can only show Alerts. Whichever extension triggered this alert should be updated to comply with beta 14.');
    }
    // End Change Compliance Warning, Remove in Beta 15
    this.activeAlerts[++this.alertId] = { componentClass, attrs };
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
