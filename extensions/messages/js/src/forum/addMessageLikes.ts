import { extend } from 'flarum/common/extend';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type Message from './components/Message';
import { likeButton, likedBy } from './utils/messageLikes';

/** With flarum/likes: a Like button on each message, and who liked it under it. */
export default function addMessageLikes() {
  extend('ext:flarum/messages/forum/components/Message', 'actionItems', function (this: Message, items: ItemList<Mithril.Children>) {
    const button = likeButton(this.attrs.message);

    if (button) items.add('like', button);
  });

  extend('ext:flarum/messages/forum/components/Message', 'footerItems', function (this: Message, items: ItemList<Mithril.Children>) {
    const liked = likedBy(this.attrs.message);

    if (liked) items.add('liked', liked);
  });
}
