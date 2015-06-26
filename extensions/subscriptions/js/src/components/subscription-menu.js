import Component from 'flarum/component';
import ActionButton from 'flarum/components/action-button';
import icon from 'flarum/helpers/icon';

import SubscriptionMenuItem from 'flarum-subscriptions/components/subscription-menu-item';

export default class SubscriptionMenu extends Component {
  view() {
    var discussion = this.props.discussion;
    var subscription = discussion.subscription();

    var buttonLabel = 'Follow';
    var buttonIcon = 'star-o';
    var buttonClass = 'btn-subscription-'+subscription;

    switch (subscription) {
      case 'follow':
        buttonLabel = 'Following';
        buttonIcon = 'star';
        break;

      case 'ignore':
        buttonLabel = 'Ignoring';
        buttonIcon = 'eye-slash';
    }

    var options = [
      {subscription: false, icon: 'star-o', label: 'Not Following', description: 'Be notified when @mentioned.'},
      {subscription: 'follow', icon: 'star', label: 'Following', description: 'Be notified of all replies.'},
      {subscription: 'ignore', icon: 'eye-slash', label: 'Ignoring', description: 'Never be notified. Hide from the discussion list.'}
    ];

    return m('div.dropdown.btn-group.subscription-menu', [
      ActionButton.component({
        className: 'btn btn-default '+buttonClass,
        icon: buttonIcon,
        label: buttonLabel,
        onclick: this.saveSubscription.bind(this, discussion, ['follow', 'ignore'].indexOf(subscription) !== -1 ? false : 'follow')
      }),

      m('a.dropdown-toggle.btn.btn-default.btn-icon[href=javascript:;][data-toggle=dropdown]', {className: buttonClass}, icon('caret-down icon-caret')),

      m('ul.dropdown-menu.pull-right', options.map(props => {
        props.onclick = this.saveSubscription.bind(this, discussion, props.subscription);
        props.active = subscription === props.subscription;
        return m('li', SubscriptionMenuItem.component(props));
      }))
    ]);
  }

  saveSubscription(discussion, subscription) {
    discussion.save({subscription});
  }
}
