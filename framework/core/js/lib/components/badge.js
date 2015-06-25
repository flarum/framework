import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';

export default class Badge extends Component {
  view(ctrl) {
    var iconName = this.props.icon;
    var label = this.props.title = this.props.label;
    delete this.props.icon, this.props.label;
    this.props.config = function(element, isInitialized) {
      if (isInitialized) return;
      $(element).tooltip();
    };
    this.props.className = 'badge '+(this.props.className || '');
    this.props.key = this.props.className;

    return m('span', this.props, [
      icon(iconName+' icon-glyph'),
      m('span.label', label)
    ]);
  }
}
