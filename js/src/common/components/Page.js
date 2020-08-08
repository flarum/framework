import Component from '../Component';
import PageState from '../states/PageState';

/**
 * The `Page` component
 *
 * @abstract
 */
export default class Page extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    app.previous = app.current;
    app.current = new PageState(this.constructor);

    app.drawer.hide();
    app.modal.close();

    /**
     * A class name to apply to the body while the route is active.
     *
     * @type {String}
     */
    this.bodyClass = '';
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    if (this.bodyClass) {
      $('#app').addClass(this.bodyClass);
    }
  }

  onremove() {
    if (this.bodyClass) {
      $('#app').removeClass(this.bodyClass);
    }
  }
}
