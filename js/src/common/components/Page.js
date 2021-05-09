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
    app.current = new PageState(this.constructor, { routeName: this.attrs.routeName });

    app.drawer.hide();
    app.modal.close();

    /**
     * A class name to apply to the body while the route is active.
     *
     * @type {String}
     */
    this.bodyClass = '';

    /**
     * Whether we should scroll to the top of the page when its rendered.
     *
     * @type {Boolean}
     */
    this.scrollTopOnCreate = true;

    /**
     * Whether the browser should restore scroll state on refreshes.
     *
     * @type {Boolean}
     */
    this.useBrowserScrollRestoration = true;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    if (this.bodyClass) {
      $('#app').addClass(this.bodyClass);
    }

    if (this.scrollTopOnCreate) {
      $(window).scrollTop(0);
    }

    if ('scrollRestoration' in history) {
      history.scrollRestoration = this.useBrowserScrollRestoration ? 'auto' : 'manual';
    }
  }

  onremove(vnode) {
    super.onremove(vnode);

    if (this.bodyClass) {
      $('#app').removeClass(this.bodyClass);
    }
  }
}
