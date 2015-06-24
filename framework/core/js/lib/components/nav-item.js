import Component from 'flarum/component'
import icon from 'flarum/helpers/icon'

export default class NavItem extends Component {
  view() {
    var active = this.constructor.active(this.props);
    return m('li'+(active ? '.active' : ''), m('a.has-icon', {
      href: this.props.href,
      onclick: this.props.onclick,
      config: m.route
    }, [
      icon(this.props.icon+' icon'),
      this.props.label, ' ',
      this.props.badge ? m('span.count', this.props.badge) : ''
    ]))
  }

  static active(props) {
    return typeof props.active !== 'undefined' ? props.active : m.route() === props.href;
  }
}
