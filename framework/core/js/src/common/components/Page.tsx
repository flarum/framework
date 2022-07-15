import type Mithril from 'mithril';
import app from '../app';
import Component from '../Component';
import PageState from '../states/PageState';

export interface IPageAttrs {
  key?: number;
  routeName: string;
}

/**
 * The `Page` component
 *
 * @abstract
 */
export default abstract class Page<CustomAttrs extends IPageAttrs = IPageAttrs, CustomState = undefined> extends Component<CustomAttrs, CustomState> {
  /**
   * A class name to apply to the body while the route is active.
   */
  protected bodyClass = '';

  /**
   * Whether we should scroll to the top of the page when its rendered.
   */
  protected scrollTopOnCreate = true;

  /**
   * Whether the browser should restore scroll state on refreshes.
   */
  protected useBrowserScrollRestoration = true;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    app.previous = app.current;
    app.current = new PageState(this.constructor, { routeName: this.attrs.routeName });

    app.drawer.hide();
    app.modal.close();
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
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

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    if (this.bodyClass) {
      $('#app').removeClass(this.bodyClass);
    }
  }
}
