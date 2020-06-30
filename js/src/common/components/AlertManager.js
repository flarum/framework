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
        {Object.entries(this.state.getActiveAlerts()).map(([key, alert]) => (
          <div className="AlertManager-alert">
            {(alert.componentClass || Alert).component({ ...alert.attrs, ondismiss: this.state.dismiss.bind(this.state, key) })}
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
