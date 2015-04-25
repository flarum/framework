import Component from 'flarum/component';

export default class LoadingIndicator extends Component {
  view() {
    var size = this.props.size || 'small';
    delete this.props.size;

    this.props.config = function(element) {
      $.fn.spin.presets[size].zIndex = 'auto';
      $(element).spin(size);
    };

    return m('div.loading-indicator', this.props, m.trust('&nbsp;'));
  }
}
