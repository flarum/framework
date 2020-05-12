import Component from '../Component';
import Alert from './Alert';

/**
 * The `AlertManager` component provides an area in which `Alert` components can
 * be shown and dismissed.
 */
export default class AlertManager extends Component {
  init() {
    this.state = this.props.state;
  }

  view() {
    return (
      <div className="AlertManager">
        {this.state.activeAlerts.map((alert) => (
          <div className="AlertManager-alert">
            <Alert state={alert} ondismiss={this.state.dismiss.bind(this.state, alert.key)} />
          </div>
        ))}
      </div>
    );
  }

  config(isInitialized, context) {
    // Since this component is 'above' the content of the page (that is, it is a
    // part of the global UI that persists between routes), we will flag the DOM
    // to be retained across route changes.
    context.retain = true;
  }
}
