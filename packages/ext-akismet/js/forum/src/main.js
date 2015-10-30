import { extend, override } from 'flarum/extend';
import app from 'flarum/app';

import PostControls from 'flarum/utils/PostControls';
import CommentPost from 'flarum/components/CommentPost';

app.initializers.add('flarum-akismet', () => {
  extend(PostControls, 'destructiveControls', function(items, post) {
    if (items.has('approve')) {
      const flags = post.flags();

      if (flags && flags.some(flag => flag.type() === 'akismet')) {
        items.get('approve').props.children = 'Not Spam';
      }
    }
  });

  override(CommentPost.prototype, 'flagReason', function(original, flag) {
    if (flag.type() === 'akismet') {
      return 'Akismet flagged as Spam';
    }

    return original(flag);
  });
}, -20); // run after the approval extension
