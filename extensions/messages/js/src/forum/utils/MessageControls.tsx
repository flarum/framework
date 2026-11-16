import ItemList from 'flarum/common/utils/ItemList';
import Separator from 'flarum/common/components/Separator';
import type Mithril from 'mithril';
import type DialogMessage from '../../common/models/DialogMessage';
import type Dialog from '../../common/models/Dialog';
import type Message from '../components/Message';
import Button from 'flarum/common/components/Button';
import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';
import removeMessage from './removeMessage';

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
    const items = new ItemList<Mithril.Children>();

    if (message.canEdit() && !message.isHidden()) {
      items.add(
        'edit',
        <Button icon="fas fa-pencil-alt" onclick={() => this.editAction(message)}>
          {app.translator.trans('flarum-messages.forum.message_controls.edit_button')}
        </Button>
      );
    }

    return items;
  },

  moderationControls(message: DialogMessage, context: Message) {
    return new ItemList<Mithril.Children>();
  },

  /**
   * As for posts: "Delete" can be undone; a deleted message can be restored,
   * or deleted for good.
   */
  destructiveControls(message: DialogMessage, context: Message) {
    const items = new ItemList<Mithril.Children>();

    if (message.canHide()) {
      items.add(
        message.isHidden() ? 'restore' : 'hide',
        message.isHidden() ? (
          <Button icon="fas fa-reply" onclick={() => this.restoreAction(message)}>
            {app.translator.trans('flarum-messages.forum.message_controls.restore_button')}
          </Button>
        ) : (
          <Button icon="far fa-trash-alt" onclick={() => this.hideAction(message)}>
            {app.translator.trans('flarum-messages.forum.message_controls.delete_button')}
          </Button>
        )
      );
    }

    // Deleting for good follows deleting, as for posts; someone who may only
    // delete for good gets to it straight away.
    if (message.canDelete() && (message.isHidden() || !message.canHide())) {
      items.add(
        'delete',
        <Button icon="fas fa-times" onclick={() => this.deleteAction(message, context)}>
          {app.translator.trans('flarum-messages.forum.message_controls.delete_forever_button')}
        </Button>
      );
    }

    return items;
  },

  editAction(message: DialogMessage) {
    // Core's composer body is a chunk of its own, which this one extends.
    return import('flarum/forum/components/ComposerBody').then(() =>
      app.composer.load(() => import('../components/EditMessageComposer'), { message }).then(() => app.composer.show())
    );
  },

  hideAction(message: DialogMessage) {
    if (!confirm(extractText(app.translator.trans('flarum-messages.forum.message_controls.delete_confirmation')))) return;

    message.pushAttributes({ isHidden: true, hiddenAt: new Date().toISOString() });
    m.redraw();

    return message.save({ isHidden: true }).then(
      () => m.redraw(),
      () => {
        message.pushAttributes({ isHidden: false, hiddenAt: null });
        m.redraw();
      }
    );
  },

  restoreAction(message: DialogMessage) {
    return message.save({ isHidden: false }).then(() => m.redraw());
  },

  deleteAction(message: DialogMessage, context: Message) {
    if (!confirm(extractText(app.translator.trans('flarum-messages.forum.message_controls.delete_forever_confirmation')))) return;

    const dialogId = message.dialog() ? (message.dialog() as Dialog).id()! : null;

    return message.delete().then(() => removeMessage(message, dialogId, { state: context.attrs.state }));
  },
};

export default MessageControls;
