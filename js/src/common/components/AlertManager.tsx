import Component from '../Component';
import AlertState from '../states/AlertState';
import Alert, { AlertData } from './Alert';

/**
 * The `AlertManager` component provides an area in which `Alert` components can
 * be shown and dismissed.
 */
export default class AlertManager extends Component {
    /**
     * An array of Alert components which are currently showing.
     */
    protected states: AlertState[] = [];

    view() {
        return (
            <div className="AlertManager">
                {this.states.map((state) => (
                    <div className="AlertManager-alert">
                        <Alert state={state} ondismiss={this.dismiss.bind(this)} />
                    </div>
                ))}
            </div>
        );
    }

    /**
     * Show an Alert in the alerts area.
     */
    public show(state: AlertState | AlertData): number {
        if (!(state instanceof AlertState)) state = new AlertState(state);

        this.states.push(state as AlertState);
        m.redraw();

        return state.key;
    }

    /**
     * Dismiss an alert.
     */
    public dismiss(keyOrState?: AlertState | number) {
        if (!keyOrState) return;

        const key = keyOrState instanceof AlertState ? keyOrState.key : keyOrState;

        let index = this.states.indexOf(this.states.filter((a) => a.key == key)[0]);

        if (index !== -1) {
            this.states.splice(index, 1);
            m.redraw();
        }
    }

    /**
     * Clear all alerts.
     */
    public clear() {
        this.states = [];
        m.redraw();
    }
}
