import Alert from '../components/Alert';

export default class AlertState {
  constructor(alertClass = Alert, attrs = {}, alertKey = AlertState.genAlertId()) {
    this.alertClass = alertClass;
    this.attrs = attrs;
    this.alertKey = alertKey;
  }

  static genAlertId() {
    return Math.floor(Math.random() * 100000000); // Generate a big random integer to avoid collisions.
  }
}
