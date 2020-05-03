import Component from '../../common/Component';

/**
 * The `Page` component
 */
export default abstract class Page extends Component {
    /**
     * A class name to apply to the body while the route is active.
     */
    bodyClass: string = '';

    oninit(vnode) {
        super.oninit(vnode);

        app.previous = app.current;
        app.current = this;

        if (this.bodyClass) {
            $('#app').addClass(this.bodyClass);
        }
    }

    oncreate(vnode) {
        super.oncreate(vnode);

        app.modal.close();
    }

    onremove(vnode) {
        super.onremove(vnode);

        $('#app').removeClass(this.bodyClass);
    }
}
