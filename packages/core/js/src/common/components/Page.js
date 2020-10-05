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

    this.onNewRoute();

    /**
     * A class name to apply to the body while the route is active.
     *
     * @type {String}
     */
    this.bodyClass = '';
  }

  /**
   * A collections of actions to run when the route changes.
   * This is extracted here, and not hardcoded in oninit, as oninit is not called
   * when a different route is handled by the same component, but we still need to
   * adjust the current route name.
   */
  onNewRoute() {
    app.previous = app.current;
    app.current = new PageState(this.constructor, { routeName: this.attrs.routeName });

    app.drawer.hide();
    app.modal.close();
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
