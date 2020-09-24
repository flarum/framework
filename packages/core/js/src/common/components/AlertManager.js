import Component from '../Component';
import Alert from './Alert';

/**
 * The `AlertManager` component provides an area in which `Alert` components can
 * be shown and dismissed.
 */
export default class AlertManager extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.state = this.attrs.state;
  }

  view() {
    return (
      <div className="AlertManager">
        {Object.entries(this.state.getActiveAlerts()).map(([key, alert]) => (
          <div className="AlertManager-alert">
            <alert.componentClass {...alert.attrs} ondismiss={this.state.dismiss.bind(this.state, key)}>
              {alert.children}
            </alert.componentClass>
          </div>
        ))}
      </div>
    );
  }
}
