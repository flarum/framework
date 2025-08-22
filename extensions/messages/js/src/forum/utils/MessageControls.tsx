import ItemList from 'flarum/common/utils/ItemList';
import Separator from 'flarum/common/components/Separator';
import type Mithril from 'mithril';
import type DialogMessage from '../../common/models/DialogMessage';
import type Message from '../components/Message';
import Button from 'flarum/common/components/Button';
import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';

const MessageControls = {
  controls(message: DialogMessage, context: Message<any>) {
    const items = new ItemList<Mithril.Children>();

    Object.entries(this.sections()).forEach(([section, method]) => {
      const controls = method.call(this, message, context).toArray();

      if (controls.length) {
        controls.forEach((item) => items.add(item.itemName, item));
        items.add(section + 'Separator', <Separator />);
      }
    });

    return items;
  },

  sections() {
    return {
      user: this.userControls,
      moderation: this.moderationControls,
      destructive: this.destructiveControls,
    };
  },

  userControls(message: DialogMessage, context: Message) {
    return new ItemList<Mithril.Children>();
  },

  moderationControls(message: DialogMessage, context: Message) {
    return new ItemList<Mithril.Children>();
  },

  destructiveControls(message: DialogMessage, context: Message) {
    const items = new ItemList<Mithril.Children>();

    if (message.canDelete()) {
      items.add(
        'delete',
        <Button icon="far fa-trash-alt" onclick={() => this.deleteAction(message, context)}>
          {app.translator.trans('flarum-messages.forum.message_controls.delete_button')}
        </Button>
      );
    }

    return items;
  },

  deleteAction(message: DialogMessage, context: Message) {
    if (!confirm(extractText(app.translator.trans('flarum-messages.forum.message_controls.delete_confirmation')))) return;

    return message.delete().then(() => {
      context.attrs.state.remove(message);

      const noMessagesLeft =
        context.attrs.state.getAllItems().filter((m) => {
          const mDialog = m.dialog();
          const messageDialog = message.dialog();

          if (!mDialog || !messageDialog) return false;

          return mDialog?.id() === messageDialog!.id();
        }).length === 0;

      if (noMessagesLeft && message.dialog()) {
        app.dialogs.remove(message.dialog()!);
        m.route.set(app.route('messages'));
      }

      m.redraw();
    });
  },
};

export default MessageControls;
