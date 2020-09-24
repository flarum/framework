import { extend } from 'flarum/extend';
import CommentPost from 'flarum/components/CommentPost';

import PostQuoteButton from './fragments/PostQuoteButton';
import selectedText from './utils/selectedText';

export default function addPostQuoteButton() {
  extend(CommentPost.prototype, 'oncreate', function() {
    const post = this.attrs.post;

    if (post.isHidden() || (app.session.user && !post.discussion().canReply())) return;

    const $postBody = this.$('.Post-body');

    // Wrap the quote button in a wrapper element so that we can render
    // button into it.
    const $container = $('<div class="Post-quoteButtonContainer"></div>');

    const button = new PostQuoteButton(post);

    const handler = function(e) {
      setTimeout(() => {
        const content = selectedText($postBody);
        if (content) {
          button.content = content;
          m.render($container[0], button.render());

          const rects = window.getSelection().getRangeAt(0).getClientRects();
          const firstRect = rects[0];

          if (e.clientY < firstRect.bottom && e.clientX - firstRect.right < firstRect.left - e.clientX) {
            button.showStart(firstRect.left, firstRect.top);
          } else {
            const lastRect = rects[rects.length - 1];
            button.showEnd(lastRect.right, lastRect.bottom);
          }
        }
      }, 1);
    };

    this.$().after($container).on('mouseup', handler);

    if ('ontouchstart' in window) {
      document.addEventListener('selectionchange', handler, false);
    }
  });
}
