import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import LinkButton from 'flarum/common/components/LinkButton';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';
import UserControls from 'flarum/forum/utils/UserControls';
import Button from 'flarum/common/components/Button';
import type Dialog from '../common/models/Dialog';
import DialogsDropdown from './components/DialogsDropdown';
import DialogListState from './states/DialogListState';

export { default as extend } from './extend';

app.initializers.add('flarum-messages', () => {
  app.dialogs = new DialogListState({}, 1);
  app.dropdownDialogs = new DialogListState(
    {
      filter: {
        unread: true,
      },
    },
    1,
    5
  );

  app.composer.composingMessageTo = function (dialog: Dialog) {
    return this.isVisible() && this.bodyMatches('flarum/messages/forum/components/MessageComposer', { dialog });
  };

  extend(IndexSidebar.prototype, 'navItems', function (items) {
    if (app.session.user) {
      items.add(
        'messages',
        <LinkButton
          href={app.route('messages')}
          icon="far fa-envelope"
          active={app.current.data.routeName && ['messages', 'dialog'].includes(app.current.data.routeName)}
        >
          {app.translator.trans('flarum-messages.forum.index.messages_link')}
        </LinkButton>,
        95
      );
    }
  });

  extend(HeaderSecondary.prototype, 'items', function (items) {
    if (app.session.user?.attribute<boolean>('canSendAnyMessage')) {
      items.add('messages', <DialogsDropdown state={app.dropdownDialogs} />, 15);
    }
  });

  // @ts-ignore
  extend(UserControls, 'userControls', (items, user) => {
    if (app.session.user?.attribute<boolean>('canSendAnyMessage')) {
      items.add(
        'sendMessage',
        <Button
          icon="fas fa-envelope"
          onclick={() => {
            import('flarum/forum/components/ComposerBody').then(() => {
              app.composer
                .load(() => import('./components/MessageComposer'), {
                  user: app.session.user,
                  recipients: [user],
                })
                .then(() => app.composer.show());
            });
          }}
        >
          {app.translator.trans('flarum-messages.forum.user_controls.send_message_button')}
        </Button>
      );
    }
  });

  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', function (items) {
    items.add('messageReceived', {
      name: 'messageReceived',
      icon: 'fas fa-envelope',
      label: app.translator.trans('flarum-messages.forum.settings.notify_message_received_label'),
    });
  });
});
