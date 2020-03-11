import MicroModal from 'micromodal';

import Component, { ComponentProps } from '../Component';
import Modal from './Modal';

/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component {
    showing!: boolean;
    hideTimeout!: number;

    modal: typeof Modal | null = null;
    modalProps: ComponentProps = {};

    component: Modal | null = null;

    oncreate(vnode) {
        super.oncreate(vnode);

        app.modal = this;
    }

    view() {
        return (
            <div className="ModalManager modal" id="Modal" onclick={this.onclick.bind(this)} key="modal">
                {this.modal && m(this.modal, this.modalProps)}
            </div>
        );
    }

    /**
     * Show a modal dialog.
     */
    show(component: Modal) {
        if (!(component instanceof Modal) && !(component.tag?.prototype instanceof Modal)) {
            throw new Error('The ModalManager component can only show Modal components');
        }

        clearTimeout(this.hideTimeout);

        this.showing = true;
        this.modal = component.tag || component.constructor;
        this.modalProps = component.props || component.attrs || {};

        this.modalProps.oninit = this.onModalInit.bind(this);

        // if (app.current) app.current.retain = true;

        m.redraw();

        if (!$('.modal-backdrop').length) {
            $('<div />')
                .addClass('modal-backdrop')
                .appendTo('body');
        }

        MicroModal.show('Modal', {
            awaitCloseAnimation: true,
            onClose: () => {
                $('.modal-backdrop').fadeOut(200, function() {
                    this.remove();
                });

                this.showing = false;
            },
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

        this.modal = null;
        this.component = null;
        this.modalProps = {};
    }

    /**
     * Clear content from the modal area.
     */
    protected clear() {
        if (this.component) {
            this.component.onhide();
        }

        this.component = null;

        // app.current.retain = false;

        m.redraw();
    }

    /**
     * When the modal dialog is ready to be used, tell it!
     */
    protected onready() {
        if (this.component) {
            this.component.onready();
        }
    }

    /**
     * Set component in ModalManager to current vnode state - a Modal instance
     */
    protected onModalInit(vnode) {
        this.component = vnode.state;
    }
}
