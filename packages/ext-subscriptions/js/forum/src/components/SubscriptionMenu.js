import Component from 'flarum/Component';
import Button from 'flarum/components/Button';
import icon from 'flarum/helpers/icon';

import SubscriptionMenuItem from 'flarum/subscriptions/components/SubscriptionMenuItem';

export default class SubscriptionMenu extends Component {
  view() {
    const discussion = this.props.discussion;
    const subscription = discussion.subscription();

    let buttonLabel = app.trans('flarum-subscriptions.forum.follow');
    let buttonIcon = 'star-o';
    const buttonClass = 'SubscriptionMenu-button--' + subscription;

    switch (subscription) {
      case 'follow':
        buttonLabel = app.trans('flarum-subscriptions.forum.following');
        buttonIcon = 'star';
        break;

      case 'ignore':
        buttonLabel = app.trans('flarum-subscriptions.forum.ignoring');
        buttonIcon = 'eye-slash';
        break;

      default:
        // no default
    }

    const options = [
      {
        subscription: false,
        icon: 'star-o',
        label: app.trans('flarum-subscriptions.forum.not_following'),
        description: app.trans('flarum-subscriptions.forum.not_following_description')
      },
      {
        subscription: 'follow',
        icon: 'star',
        label: app.trans('flarum-subscriptions.forum.following'),
        description: app.trans('flarum-subscriptions.forum.following_description')
      },
      {
        subscription: 'ignore',
        icon: 'eye-slash',
        label: app.trans('flarum-subscriptions.forum.ignoring'),
        description: app.trans('flarum-subscriptions.forum.ignoring_description')
      }
    ];

    return (
      <div className="Dropdown ButtonGroup SubscriptionMenu">
        {Button.component({
          className: 'Button SubscriptionMenu-button ' + buttonClass,
          icon: buttonIcon,
          children: buttonLabel,
          onclick: this.saveSubscription.bind(this, discussion, ['follow', 'ignore'].indexOf(subscription) !== -1 ? false : 'follow')
        })}

        <button className={'Dropdown-toggle Button Button--icon ' + buttonClass} data-toggle="dropdown">
          {icon('caret-down', {className: 'Button-icon'})}
        </button>

        <ul className="Dropdown-menu dropdown-menu Dropdown-menu--right">
          {options.map(props => {
            props.onclick = this.saveSubscription.bind(this, discussion, props.subscription);
            props.active = subscription === props.subscription;

            return <li>{SubscriptionMenuItem.component(props)}</li>;
          })}
        </ul>
      </div>
    );
  }

  saveSubscription(discussion, subscription) {
    discussion.save({subscription});
  }
}
