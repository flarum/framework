export default class AlertManagerState {
  constructor() {
    this.activeAlerts = {};
  }

  /**
   * Show an Alert in the alerts area.
   */
  show(state) {

    this.activeAlerts[state.key] = state;
    m.redraw();

    return key;
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
