import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';

export default class SubscriptionMenuItem extends Component {
  view() {
    return m('a.subscription-menu-item.has-icon[href=javascript:;]', {
      onclick: this.props.onclick
    }, [
      this.props.active ? icon('check icon') : '',
      m('span.label',
        icon(this.props.icon+' icon'),
        m('strong', this.props.label),
        m('span.description', this.props.description)
      )
    ]);
  }
}
