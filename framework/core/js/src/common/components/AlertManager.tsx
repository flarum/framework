import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';
import type AlertManagerState from '../states/AlertManagerState';

export interface IAlertManagerAttrs extends ComponentAttrs {
  state: AlertManagerState;
}

/**
 * The `AlertManager` component provides an area in which `Alert` components can
 * be shown and dismissed.
 */
export default class AlertManager<CustomAttrs extends IAlertManagerAttrs = IAlertManagerAttrs> extends Component<CustomAttrs> {
  alertsState!: AlertManagerState;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.alertsState = this.attrs.state;
  }

  view(): Mithril.Children {
    return (
      <div class="AlertManager">
        {Object.entries(this.alertsState.getActiveAlerts()).map(([key, alert]) => {
          const urgent = alert.attrs.type === 'error';

          return (
            <div class="AlertManager-alert" role="alert" aria-live={urgent ? 'assertive' : 'polite'}>
              <alert.componentClass {...alert.attrs} ondismiss={this.alertsState.dismiss.bind(this.alertsState, parseInt(key))}>
                {alert.children}
              </alert.componentClass>
            </div>
          );
        })}
      </div>
    );
  }
}
