import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import LinkButton from 'flarum/common/components/LinkButton';
import DialogListState from './states/DialogListState';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';
import DialogsDropdown from './components/DialogsDropdown';
import type Dialog from '../common/models/Dialog';
import UserControls from 'flarum/forum/utils/UserControls';
import Button from 'flarum/common/components/Button';

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
    const MessageComposer = flarum.reg.checkModule('flarum-messages', 'forum/components/MessageComposer');

    if (!MessageComposer) return false;

    return this.isVisible() && this.bodyMatches(MessageComposer, { dialog });
  };

  extend(IndexSidebar.prototype, 'navItems', function (items) {
    if (app.session.user) {
      items.add(
        'messages',
        <LinkButton href={app.route('messages')} icon="far fa-envelope" active={['messages', 'dialog'].includes(app.current.data.routeName)}>
          {app.translator.trans('flarum-messages.forum.index.messages_link')}
        </LinkButton>,
        95
      );
    }
  });

  extend(HeaderSecondary.prototype, 'items', function (items) {
    if (app.session.user?.attribute<boolean>('canSendAnyMessage')) {
      items.add('flags', <DialogsDropdown state={app.dropdownDialogs} />, 15);
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
});
