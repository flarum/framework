import Component, { ComponentAttrs } from '../Component';
import AlertManagerState from '../states/AlertManagerState';
import type Mithril from 'mithril';

export interface IAlertManagerAttrs extends ComponentAttrs {
  state: AlertManagerState;
}

/**
 * The `AlertManager` component provides an area in which `Alert` components can
 * be shown and dismissed.
 */
export default class AlertManager<CustomAttrs extends IAlertManagerAttrs = IAlertManagerAttrs> extends Component<CustomAttrs, AlertManagerState> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.state = this.attrs.state;
  }

  view() {
    const activeAlerts = this.state.getActiveAlerts();

    return (
      <div className="AlertManager">
        {Object.keys(activeAlerts)
          .map(Number)
          .map((key) => {
            const alert = activeAlerts[key];
            const urgent = alert.attrs.type === 'error';

            return (
              <div className="AlertManager-alert" role="alert" aria-live={urgent ? 'assertive' : 'polite'}>
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
