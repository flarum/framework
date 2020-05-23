import Alert from '../components/Alert';

export default class AlertState {
  constructor(attrs = {}, alertClass = Alert, alertKey = AlertState.genAlertId()) {
    this.attrs = attrs;
    this.alertClass = alertClass;
    this.alertKey = alertKey;
  }

  static genAlertId() {
    return Math.floor(Math.random() * 100000000); // Generate a big random integer to avoid collisions.
  }
}
