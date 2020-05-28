import Alert from '../components/Alert';

export default class AlertState {
  constructor(attrs = {}, alertClass = Alert, alertKey = AlertState.genAlertId()) {
    this.attrs = attrs;
    this.alertClass = alertClass;
    this.alertKey = alertKey;
  }

  getAttrs() {
    return this.attrs;
  }

  setAttrs(attrs) {
    this.attrs = attrs;
  }

  getClass() {
    return this.alertClass;
  }

  setClass(alertClass) {
    this.alertClass = alertClass;
  }

  getKey() {
    return this.key;
  }

  static genAlertId() {
    return Math.floor(Math.random() * 100000000); // Generate a big random integer to avoid collisions.
  }
}
