import Mithril from 'mithril';

import Component, { ComponentProps } from '../Component';
import Button from './Button';
import RequestError from '../utils/RequestError';

/**
 * The `Modal` component displays a modal dialog, wrapped in a form. Subclasses
 * should implement the `className`, `title`, and `content` methods.
 */
export default abstract class Modal<T extends ComponentProps = ComponentProps> extends Component<T> {
    /**
     * An alert component to show below the header.
     */
    alert: Mithril.Vnode;

    loading: boolean;

    view() {
        if (this.alert) {
            this.alert.attrs.dismissible = false;
        }

        return (
            <div className={`Modal modal-dialog ${this.className()}`}>
                <div className="Modal-content">
                    {this.isDismissible() ? (
                        <div className="Modal-close App-backControl">
                            {Button.component({
                                icon: 'fas fa-times',
                                onclick: this.hide.bind(this),
                                className: 'Button Button--icon Button--link',
                            })}
                        </div>
                    ) : (
                        ''
                    )}

                    <form onsubmit={this.onsubmit.bind(this)}>
                        <div className="Modal-header">
                            <h3 className="App-titleControl App-titleControl--text">{this.title()}</h3>
                        </div>

                        {this.alert && <div className="Modal-alert">{this.alert}</div>}

                        {this.content()}
                    </form>
                </div>
            </div>
        );
    }

    /**
     * Determine whether or not the modal should be dismissible via an 'x' button.
     */
    isDismissible(): boolean {
        return true;
    }

    /**
     * Get the class name to apply to the modal.
     */
    abstract className(): string;

    /**
     * Get the title of the modal dialog.
     */
    abstract title();

    /**
     * Get the content of the modal.
     */
    abstract content();

    /**
     * Handle the modal form's submit event.
     */
    onsubmit(e: Event) {}

    /**
     * Focus on the first input when the modal is ready to be used.
     */
    onready() {
        this.$('form').find('input, select, textarea').first().focus().select();
    }

    onhide() {}

    /**
     * Hide the modal.
     */
    hide() {
        app.modal.close();
    }

    /**
     * Stop loading.
     */
    loaded() {
        this.loading = false;
        m.redraw();
    }

    /**
     * Show an alert describing an error returned from the API, and give focus to
     * the first relevant field.
     */
    onerror(error: RequestError) {
        this.alert = error.alert;

        m.redraw();

        if (error.status === 422 && error.response.errors) {
            this.$(`form [name="${error.response.errors[0].source.pointer.replace('/data/attributes/', '')}"]`).select();
        } else {
            this.onready();
        }
    }
}
