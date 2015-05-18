/**

 */
export default class Component {
  /**

   */
  constructor(props) {
    this.props = props || {};

    this.element = m.prop();
  }

  /**

   */
  $(selector) {
    return selector ? $(this.element()).find(selector) : $(this.element());
  }

  onload(element) {
    this.element(element);
  }

  config() {

  }

  /**

   */
  static component(props) {
    props = props || {};
    if (this.props) {
      this.props(props);
    }
    var view = function(component) {
      component.props = props;
      var vdom = component.view();
      vdom.attrs = vdom.attrs || {};
      if (!vdom.attrs.config) {
        vdom.attrs.config = function() {
          var args = [].slice.apply(arguments);
          if (!args[1]) {
            component.onload.apply(component, args);
          }
          component.config.apply(component, args);
        }
      }
      return vdom;
    };
    view.$original = this.prototype.view;
    var output = {
      props: props,
      component: this,
      controller: this.bind(undefined, props),
      view: view
    };
    if (props.key) {
      output.attrs = {key: props.key};
    }
    return output;
  }
}
