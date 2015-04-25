import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';
import NavItem from 'flarum/components/nav-item';

export default class AdminNavItem extends NavItem {
  view() {
    var active = this.constructor.active(this.props);
    return m('li'+(active ? '.active' : ''), m('a', {href: this.props.href, config: m.route}, [
      icon(this.props.icon+' icon'),
      m('span.label', this.props.label),
      m('div.description', this.props.description)
    ]))
  }
}
