import Alert from '../components/Alert';

export default class AlertState {
  constructor(attrs = {}, alertClass = Alert) {
    this.attrs = attrs;
    this.alertClass = alertClass;
  }
}
