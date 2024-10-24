import app from 'flarum/forum/app';
import Dropdown, { IDropdownAttrs } from 'flarum/common/components/Dropdown';
import Button from 'flarum/common/components/Button';
import extractText from 'flarum/common/utils/extractText';
import DetailedDropdownItem from 'flarum/common/components/DetailedDropdownItem';
import SplitDropdown from 'flarum/common/components/SplitDropdown';
import type Discussion from 'flarum/common/models/Discussion';

export interface ISubscriptionMenuAttrs extends IDropdownAttrs {
  discussion: Discussion;
}

export default class SubscriptionMenu<CustomAttrs extends ISubscriptionMenuAttrs = ISubscriptionMenuAttrs> extends Dropdown<CustomAttrs> {
  private options: any[] = [
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

  private possibleButtonAttrs: any = {
    null: {
      icon: 'far fa-star',
      label: app.translator.trans('flarum-subscriptions.forum.sub_controls.follow_button'),
    },
    follow: {
      icon: 'fas fa-star',
      label: app.translator.trans('flarum-subscriptions.forum.sub_controls.following_button'),
    },
    ignore: {
      icon: 'far fa-eye-slash',
      label: app.translator.trans('flarum-subscriptions.forum.sub_controls.ignoring_button'),
    },
  };

  view() {
    const discussion = this.attrs.discussion;
    const subscription = discussion.subscription();

    const buttonAttrs = this.possibleButtonAttrs[subscription];

    const preferences = app.session.user!.preferences()!;
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
        buttonClassName={`SubscriptionMenu-button--${subscription}`}
        tooltip={shouldShowTooltip ? tooltipText : null}
        mainAction={
          <Button
            className={'SubscriptionMenu-button'}
            icon={buttonAttrs.icon}
            onclick={this.saveSubscription.bind(this, discussion, ['follow', 'ignore'].indexOf(subscription) !== -1 ? null : 'follow')}
          >
            {buttonAttrs.label}
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

  saveSubscription(discussion: Discussion, subscription: string | null): void {
    discussion.save({ subscription });

    // @ts-ignore
    this.$('.SubscriptionMenu-button').tooltip('hide');
  }
}
