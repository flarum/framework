export default class AlertManagerState {
  constructor() {
    this.activeAlerts = {};
  }

  getActiveAlerts() {
    return this.activeAlerts;
  }

  /**
   * Show an Alert in the alerts area.
   */
  show(alert) {
    this.activeAlerts[alert.getKey()] = state;
    m.redraw();

    return alert.getKey();
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
