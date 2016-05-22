import { extend } from 'flarum/extend';
import CommentPost from 'flarum/components/CommentPost';

import PostQuoteButton from 'flarum/mentions/components/PostQuoteButton';

export default function addPostQuoteButton() {
  extend(CommentPost.prototype, 'config', function(original, isInitialized) {
    const post = this.props.post;

    if (isInitialized || post.isHidden() || (app.session.user && !post.discussion().canReply())) return;

    const $postBody = this.$().find('.Post-body');

    // Wrap the quote button in a wrapper element so that we can render
    // button into it.
    const $container = $('<div class="Post-quoteButtonContainer"></div>');

    this.$()
      .after($container)
      .on('mouseup', function(e) {
        setTimeout(() => {
          const selection = window.getSelection();

          if (selection.rangeCount) {
            const range = selection.getRangeAt(0);
            const parent = range.commonAncestorContainer;

            if ($postBody[0] === parent || $.contains($postBody[0], parent)) {
              const content = selection.toString();

              if (content) {
                const button = new PostQuoteButton({post, content});
                m.render($container[0], button.render());

                const rects = range.getClientRects();
                const firstRect = rects[0];

                if (e.clientY < firstRect.bottom && e.clientX - firstRect.right < firstRect.left - e.clientX) {
                  button.showStart(firstRect.left, firstRect.top);
                } else {
                  const lastRect = rects[rects.length - 1];
                  button.showEnd(lastRect.right, lastRect.bottom);
                }
              }
            }
          }
        }, 1);
      });
  });
}
