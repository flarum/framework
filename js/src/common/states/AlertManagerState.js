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
  show(attrs, componentClass) {
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
