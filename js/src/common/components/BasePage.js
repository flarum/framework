import Component from '../Component';
import PageState from '../states/PageState';

/**
 * The `Page` component
 *
 * @abstract
 */
export default class BasePage extends Component {
  init() {
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

  config(isInitialized, context) {
    if (isInitialized) return;

    if (this.bodyClass) {
      $('#app').addClass(this.bodyClass);

      context.onunload = () => $('#app').removeClass(this.bodyClass);
    }
  }
}
