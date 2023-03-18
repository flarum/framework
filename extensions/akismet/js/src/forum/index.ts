import { extend, override } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import type Post from 'flarum/common/models/Post';
import type ItemList from 'flarum/common/utils/ItemList';

import PostControls from 'flarum/forum/utils/PostControls';
import PostComponent from 'flarum/forum/components/Post';
import type Mithril from 'mithril';

app.initializers.add('flarum-akismet', () => {
  extend(PostControls, 'destructiveControls', function (items: ItemList<Mithril.Children>, post: Post) {
    if (items.has('approve')) {
      const flags = post.flags();

      if (flags && flags.some((flag) => flag?.type() === 'akismet')) {
        const approveItem = items.get('approve');
        if (approveItem && typeof approveItem === 'object' && 'children' in approveItem) {
          approveItem.children = app.translator.trans('flarum-akismet.forum.post.not_spam_button');
        }
      }
    }
  });

  override(PostComponent.prototype, 'flagReason', function (original, flag) {
    if (flag.type() === 'akismet') {
      return app.translator.trans('flarum-akismet.forum.post.akismet_flagged_text');
    }

    return original(flag);
  });
});
