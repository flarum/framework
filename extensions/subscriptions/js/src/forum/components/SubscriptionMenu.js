import app from 'flarum/forum/app';
import Dropdown from 'flarum/common/components/Dropdown';
import Button from 'flarum/common/components/Button';
import extractText from 'flarum/common/utils/extractText';
import DetailedDropdownItem from 'flarum/common/components/DetailedDropdownItem';
import SplitDropdown from 'flarum/common/components/SplitDropdown';

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

    return (
      <SplitDropdown
        className="SubscriptionMenu"
        buttonClassName={buttonClass}
        tooltip={shouldShowTooltip ? tooltipText : null}
        mainAction={
          <Button
            className={'SubscriptionMenu-button'}
            icon={buttonIcon}
            onclick={this.saveSubscription.bind(this, discussion, ['follow', 'ignore'].indexOf(subscription) !== -1 ? null : 'follow')}
          >
            {buttonLabel}
          </Button>
        }
      >
        {this.options.map((attrs) => (
          <DetailedDropdownItem
            {...attrs}
            onclick={this.saveSubscription.bind(this, discussion, attrs.subscription)}
            active={subscription === attrs.subscription}
          />
        ))}
      </SplitDropdown>
    );
  }

  saveSubscription(discussion, subscription) {
    discussion.save({ subscription });

    this.$('.SubscriptionMenu-button').tooltip('hide');
  }
}
