import AlertState from './AlertState';

export default class AlertManagerState {
  constructor() {
    this.activeAlerts = {};
  }

  /**
   * Show an Alert in the alerts area.
   */
  show(attrs, key = AlertManagerState.genAlertId()) {
    const state = new AlertState(attrs);

    this.activeAlerts[key] = state;
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

  static genAlertId() {
    return Math.floor(Math.random() * 100000000); // Generate a big random integer to avoid collisions.
  }
}
