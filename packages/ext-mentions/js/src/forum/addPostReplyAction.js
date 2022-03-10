import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import CommentPost from 'flarum/forum/components/CommentPost';

import reply from './utils/reply';

export default function () {
  extend(CommentPost.prototype, 'actionItems', function (items) {
    const post = this.attrs.post;

    if (post.isHidden() || (app.session.user && !post.discussion().canReply())) return;

    items.add(
      'reply',
      <Button className="Button Button--link" onclick={() => reply(post)}>
        {app.translator.trans('flarum-mentions.forum.post.reply_link')}
      </Button>
    );
  });
}
