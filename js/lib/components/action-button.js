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

    if (attrs.disabled) {
      attrs.className = (attrs.className || '')+' disabled';
      delete attrs.onclick;
      delete attrs.disabled;
    }

    attrs.href = attrs.href || 'javascript:;';
    return m('a'+(iconName ? '.has-icon' : ''), attrs, [
      iconName ? icon(iconName+' icon') : '', ' ',
      m('span.label', label)
    ]);
  }
}
