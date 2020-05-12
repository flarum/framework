import AlertState from './AlertState';

export default class AlertManagerState {
  constructor() {
    this.activeAlerts = [];
  }

  /**
   * Show an Alert in the alerts area.
   */
  show(propsOrState) {
    const state = propsOrState instanceof AlertState ? propsOrState : new AlertState(propsOrState);

    this.activeAlerts.push(state);
    m.redraw();

    return state.key;
  }

  /**
   * Dismiss an alert.
   */
  dismiss(keyOrState) {
    if (!keyOrState) return;

    const key = keyOrState instanceof AlertState ? keyOrState.key : keyOrState;

    let index = this.activeAlerts.indexOf(this.activeAlerts.filter((a) => a.key == key)[0]);

    if (index !== -1) {
      this.activeAlerts.splice(index, 1);
      m.redraw();
    }
  }
  /**
   * Clear all alerts.
   *
   * @public
   */
  clear() {
    this.activeAlerts = [];
    m.redraw();
  }
}
