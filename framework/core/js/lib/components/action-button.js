import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';

export default class ActionButton extends Component {
  view() {
    var attrs = {};
    for (var i in this.props) { attrs[i] = this.props[i]; }

    var iconName = attrs.icon;
    delete attrs.icon;

    var label = attrs.label;
    delete attrs.label;

    attrs.href = attrs.href || 'javascript:;';
    return m('a', attrs, [
      iconName ? icon(iconName+' icon-glyph') : '',
      m('span.label', label)
    ]);
  }
}
