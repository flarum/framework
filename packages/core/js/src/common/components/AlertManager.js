import Component from '../Component';

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
      <div class="AlertManager">
        {Object.entries(this.state.getActiveAlerts()).map(([key, alert]) => {
          const urgent = alert.attrs.type === 'error';

          return (
            <div class="AlertManager-alert" role="alert" aria-live={urgent ? 'assertive' : 'polite'}>
              <alert.componentClass {...alert.attrs} ondismiss={this.state.dismiss.bind(this.state, key)}>
                {alert.children}
              </alert.componentClass>
            </div>
          );
        })}
      </div>
    );
  }
}
