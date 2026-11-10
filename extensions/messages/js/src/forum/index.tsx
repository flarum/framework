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
import type User from 'flarum/common/models/User';
import extendRealtime from './extendRealtime';
import addMessageSearch from './addMessageSearch';

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
    // The composer is loaded with `replyingTo`, so that is what must match.
    return this.isVisible() && this.bodyMatches('flarum/messages/forum/components/MessageComposer', { replyingTo: dialog });
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

  // For every member, not only those who can send: someone without messaging
  // permission can still be messaged by staff, and needs to see it.
  extend(HeaderSecondary.prototype, 'items', function (items) {
    if (app.session.user) {
      items.add('messages', <DialogsDropdown state={app.dropdownDialogs} />, 15);
    }
  });

  // @ts-ignore
  extend(UserControls, 'userControls', (items, user: User) => {
    // Not on accounts nobody can write to, such as those anonymised by flarum/gdpr.
    if (app.session.user?.canSendAnyMessage() && user !== app.session.user && user.canMessage() !== false) {
      // Someone who can't send messages couldn't reply, so only those allowed
      // to message users without messaging permission may write to them.
      const available = user.canSendAnyMessage() || app.session.user.canMessageUsersWithoutPermission();

      items.add(
        'sendMessage',
        <Button
          icon="fas fa-envelope"
          disabled={!available}
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
          helperText={available ? null : app.translator.trans('flarum-messages.forum.user_controls.cannot_reply_text')}
        >
          {app.translator.trans('flarum-messages.forum.user_controls.send_message_button')}
        </Button>
      );
    }
  });

  addMessageSearch();

  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', function (items) {
    items.add('messageReceived', {
      name: 'messageReceived',
      icon: 'fas fa-envelope',
      label: app.translator.trans('flarum-messages.forum.settings.notify_message_received_label'),
    });
  });

  // Register realtime events with flarum/realtime when enabled.
  // New dialog messages update the header counter and message stream in real-time.
  // The typing indicator attaches to per-dialog private Pusher channels.
  if ('flarum-realtime' in flarum.extensions) {
    extendRealtime();
  }
});
