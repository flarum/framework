import Component from 'flarum/Component';
import Button from 'flarum/components/Button';
import icon from 'flarum/helpers/icon';

import SubscriptionMenuItem from 'flarum/subscriptions/components/SubscriptionMenuItem';

export default class SubscriptionMenu extends Component {
  view() {
    const discussion = this.props.discussion;
    const subscription = discussion.subscription();

    let buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.follow_button');
    let buttonIcon = 'star-o';
    const buttonClass = 'SubscriptionMenu-button--' + subscription;

    switch (subscription) {
      case 'follow':
        buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.following_button');
        buttonIcon = 'star';
        break;

      case 'ignore':
        buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_button');
        buttonIcon = 'eye-slash';
        break;

      default:
        // no default
    }

    const options = [
      {
        subscription: false,
        icon: 'star-o',
        label: app.translator.trans('flarum-subscriptions.forum.sub_controls.not_following_button'),
        description: app.translator.trans('flarum-subscriptions.forum.sub_controls.not_following_text')
      },
      {
        subscription: 'follow',
        icon: 'star',
        label: app.translator.trans('flarum-subscriptions.forum.sub_controls.following_button'),
        description: app.translator.trans('flarum-subscriptions.forum.sub_controls.following_text')
      },
      {
        subscription: 'ignore',
        icon: 'eye-slash',
        label: app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_button'),
        description: app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_text')
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
