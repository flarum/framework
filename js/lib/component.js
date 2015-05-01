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

  /**

   */
  static component(props) {
    props = props || {};
    if (this.props) {
      props = this.props(props);
    }
    var view = function(component) {
      component.props = props;
      return component.view();
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
