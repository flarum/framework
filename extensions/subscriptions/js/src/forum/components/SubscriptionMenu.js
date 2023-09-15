import app from 'flarum/forum/app';
import Dropdown from 'flarum/common/components/Dropdown';
import Button from 'flarum/common/components/Button';
import Tooltip from 'flarum/common/components/Tooltip';
import icon from 'flarum/common/helpers/icon';
import extractText from 'flarum/common/utils/extractText';
import classList from 'flarum/common/utils/classList';

import SubscriptionMenuItem from './SubscriptionMenuItem';

export default class SubscriptionMenu extends Dropdown {
  oninit(vnode) {
    super.oninit(vnode);

    this.options = [
      {
        subscription: null,
        icon: 'far fa-star',
        label: app.translator.trans('flarum-subscriptions.forum.sub_controls.not_following_button'),
        description: app.translator.trans('flarum-subscriptions.forum.sub_controls.not_following_text'),
      },
      {
        subscription: 'follow',
        icon: 'fas fa-star',
        label: app.translator.trans('flarum-subscriptions.forum.sub_controls.following_button'),
        description: app.translator.trans('flarum-subscriptions.forum.sub_controls.following_text'),
      },
      {
        subscription: 'ignore',
        icon: 'far fa-eye-slash',
        label: app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_button'),
        description: app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_text'),
      },
    ];
  }

  view() {
    const discussion = this.attrs.discussion;
    const subscription = discussion.subscription();

    let buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.follow_button');
    let buttonIcon = 'far fa-star';
    const buttonClass = 'SubscriptionMenu-button--' + subscription;

    switch (subscription) {
      case 'follow':
        buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.following_button');
        buttonIcon = 'fas fa-star';
        break;

      case 'ignore':
        buttonLabel = app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_button');
        buttonIcon = 'far fa-eye-slash';
        break;

      default:
      // no default
    }

    const preferences = app.session.user.preferences();
    const notifyEmail = preferences['notify_newPost_email'];
    const notifyAlert = preferences['notify_newPost_alert'];
    const tooltipText = extractText(
      app.translator.trans(
        notifyEmail ? 'flarum-subscriptions.forum.sub_controls.notify_email_tooltip' : 'flarum-subscriptions.forum.sub_controls.notify_alert_tooltip'
      )
    );

    const shouldShowTooltip = (notifyEmail || notifyAlert) && subscription === null;

    const button = (
      <Button
        className={classList('Button', 'SubscriptionMenu-button', buttonClass)}
        icon={buttonIcon}
        onclick={this.saveSubscription.bind(this, discussion, ['follow', 'ignore'].indexOf(subscription) !== -1 ? null : 'follow')}
      >
        {buttonLabel}
      </Button>
    );

    return (
      <div className="Dropdown ButtonGroup SubscriptionMenu">
        {shouldShowTooltip ? (
          <Tooltip text={tooltipText} position="bottom">
            {button}
          </Tooltip>
        ) : (
          button
        )}

        <button className={classList('Dropdown-toggle Button Button--icon', buttonClass)} data-toggle="dropdown">
          {icon('fas fa-caret-down', { className: 'Button-icon' })}
        </button>

        <ul className="Dropdown-menu dropdown-menu Dropdown-menu--right">
          {this.options.map((attrs) => (
            <li>
              <SubscriptionMenuItem
                {...attrs}
                onclick={this.saveSubscription.bind(this, discussion, attrs.subscription)}
                active={subscription === attrs.subscription}
              />
            </li>
          ))}
        </ul>
      </div>
    );
  }

  saveSubscription(discussion, subscription) {
    discussion.save({ subscription });

    this.$('.SubscriptionMenu-button').tooltip('hide');
  }
}
