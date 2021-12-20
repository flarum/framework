import Component from '../../common/Component';

export default class DashboardWidget extends Component {
  view() {
    return <div className={'DashboardWidget Widget ' + this.className()}>{this.content()}</div>;
  }

  /**
   * Get the class name to apply to the widget.
   *
   * @return {string}
   */
  className() {
    return '';
  }

  /**
   * Get the content of the widget.
   *
   * @return {import('mithril').Children}
   */
  content() {
    return null;
  }
}
