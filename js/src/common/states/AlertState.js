export default class AlertState {
  constructor(props = {}, key = Date.now()) {
    this.props = props;
    this.key = key;
  }
}
