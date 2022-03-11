import { extend, override } from 'flarum/common/extend';
import app from 'flarum/forum/app';

import PostControls from 'flarum/forum/utils/PostControls';
import CommentPost from 'flarum/forum/components/CommentPost';
import ItemList from 'flarum/common/utils/ItemList';
import Post from 'flarum/common/models/Post';

app.initializers.add('flarum-akismet', () => {
  extend(PostControls, 'destructiveControls', function (items: ItemList, post: Post) {
    if (items.has('approve')) {
      const flags = post.flags();

      if (flags && flags.some((flag) => flag.type() === 'akismet')) {
        items.get('approve').children = app.translator.trans('flarum-akismet.forum.post.not_spam_button');
      }
    }
  });

  override(CommentPost.prototype, 'flagReason', function (original, flag) {
    if (flag.type() === 'akismet') {
      return app.translator.trans('flarum-akismet.forum.post.akismet_flagged_text');
    }

    return original(flag);
  });
});
