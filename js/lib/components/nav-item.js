import Component from 'flarum/component'
import icon from 'flarum/helpers/icon'

export default class NavItem extends Component {
  view() {
    var active = NavItem.active(this.props);
    return m('li'+(active ? '.active' : ''), m('a', {href: this.props.href, config: m.route}, [
      icon(this.props.icon),
      this.props.label, ' ',
      m('span.count', this.props.badge)
    ]))
  }

  static active(props) {
    return typeof props.active !== 'undefined' ? props.active : m.route() === props.href;
  }
}
