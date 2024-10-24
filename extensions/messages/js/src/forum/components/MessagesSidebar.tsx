import app from 'flarum/forum/app';
import IndexSidebar, { type IndexSidebarAttrs } from 'flarum/forum/components/IndexSidebar';
import Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';
import Button from 'flarum/common/components/Button';

export interface IMessagesSidebarAttrs extends IndexSidebarAttrs {}

export default class MessagesSidebar<CustomAttrs extends IMessagesSidebarAttrs = IMessagesSidebarAttrs> extends IndexSidebar<CustomAttrs> {
  static initAttrs(attrs: IMessagesSidebarAttrs) {
    attrs.className = 'MessagesPage-nav';
  }

  items(): ItemList<Mithril.Children> {
    const items = super.items();

    const canSendAnyMessage = app.session.user!.attribute<boolean>('canSendAnyMessage');

    items.remove('newDiscussion');

    items.add(
      'newMessage',
      <Button
        icon="fas fa-edit"
        className="Button Button--primary IndexPage-newDiscussion MessagesPage-newMessage"
        itemClassName="App-primaryControl"
        onclick={() => {
          return this.newMessageAction();
        }}
        disabled={!canSendAnyMessage}
      >
        {app.translator.trans('flarum-messages.forum.messages_page.new_message_button')}
      </Button>,
      10
    );

    return items;
  }

  /**
   * Open the composer for a new message.
   */
  newMessageAction(): Promise<unknown> {
    return import('flarum/forum/components/ComposerBody').then(() => {
      app.composer
        .load(() => import('./MessageComposer'), {
          user: app.session.user,
          onsubmit: () => {
            app.dialogs.refresh();
          },
        })
        .then(() => app.composer.show());

      return app.composer;
    });
  }
}
