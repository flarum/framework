import MicroModal from 'micromodal';

import Component from '../Component';
import Modal from './Modal';
import {Vnode} from "mithril";

/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component {
    private node: Vnode;

    showing: boolean;
    hideTimeout: number;
    component?: Modal;

    oncreate(vnode) {
        super.oncreate(vnode);

        app.modal = this;
    }

    view() {
        return (
            <div className="ModalManager modal" id="Modal" onclick={this.onclick.bind(this)} key="modal">
                {this.node}
            </div>
        );
    }

    /**
     * Show a modal dialog.
     */
    show(component) {
        if (!(component instanceof Modal) && !(component.tag?.prototype instanceof Modal)) {
            throw new Error('The ModalManager component can only show Modal components');
        }

        clearTimeout(this.hideTimeout);

        this.showing = true;
        this.node = component.tag ? component : m(component);

        // if (app.current) app.current.retain = true;

        m.redraw();

        if (!$('.modal-backdrop').length) {
            $('<div />').addClass('modal-backdrop')
                .appendTo('body');
        }

        MicroModal.show('Modal', {
            awaitCloseAnimation: true,
            onClose: () => {
                $('.modal-backdrop').fadeOut(200, function () {
                    this.remove();
                });

                this.showing = false;
            }
        });

        this.onready();
    }

    onclick(e) {
        if (e.target === this.element) {
            this.close();
        }
    }

    /**
     * Close the modal dialog.
     */
    close() {
        if (!this.showing) return;

        // Don't hide the modal immediately, because if the consumer happens to call
        // the `show` method straight after to show another modal dialog, it will
        // cause the new modal dialog to disappear. Instead we will wait for a tiny
        // bit to give the `show` method the opportunity to prevent this from going
        // ahead.
        this.hideTimeout = setTimeout(() => MicroModal.close('Modal'));
    }

    /**
     * Clear content from the modal area.
     *
     * @protected
     */
    clear() {
        if (this.component) {
            this.component.onhide();
        }

        this.component = null;

        // app.current.retain = false;

        m.redraw();
    }

    /**
     * When the modal dialog is ready to be used, tell it!
     *
     * @protected
     */
    onready() {
        if (this.component?.onready) {
            this.component.onready();
        }
    }
}
