import Alert from "../components/Alert";

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
  show(attrs, componentClass=Alert) {
    // Breaking Change Compliance Warning, Remove in Beta 15
    if (!(componentClass === Alert || componentClass.prototype instanceof Alert)) {
      throw new Error('The AlertManager can only show Alerts');
    }
    if (componentClass.init) {
      throw new Error('The type parameter must be an alert class, not an alert instance');
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
