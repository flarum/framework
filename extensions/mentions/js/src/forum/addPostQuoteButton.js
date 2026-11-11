import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import CommentPost from 'flarum/forum/components/CommentPost';

import PostQuoteButton from './fragments/PostQuoteButton';
import selectedText from './utils/selectedText';

export default function addPostQuoteButton() {
  let pointerType;
  let pointerDownTarget;
  let selectionOnPointerDown;
  const selectionSnapshot = () => {
    const selection = window.getSelection();
    return [selection?.anchorNode, selection?.anchorOffset, selection?.focusNode, selection?.focusOffset];
  };

  document.addEventListener(
    'pointerdown',
    (event) => {
      pointerType = event.pointerType;
      pointerDownTarget = event.target;
      selectionOnPointerDown = selectionSnapshot();
    },
    true
  );
  document.addEventListener(
    'keydown',
    (event) => {
      if (!['Shift', 'Control', 'Alt', 'Meta'].includes(event.key)) pointerType = undefined;
    },
    true
  );

  extend(CommentPost.prototype, 'oncreate', function () {
    const post = this.attrs.post;

    if (post.isHidden() || (app.session.user && !post.discussion().canReply())) return;

    const postElement = this.$()[0];
    const $postBody = this.$('.Post-body');

    // Wrap the quote button in a wrapper element so that we can render
    // button into it.
    const $container = $('<div class="Post-quoteButtonContainer"></div>');

    const button = new PostQuoteButton(post);

    const handler = function (e) {
      if (e.type === 'mouseup' && pointerType === 'touch') return;

      if (
        e.type === 'mouseup' &&
        pointerType === 'mouse' &&
        !postElement.contains(pointerDownTarget) &&
        selectionSnapshot().every((value, index) => value === selectionOnPointerDown[index])
      )
        return;

      setTimeout(() => {
        const content = selectedText($postBody);
        if (content) {
          if (e.type === 'selectionchange' && pointerType === 'mouse') return;

          button.content = content;
          m.render($container[0], button.render());

          // A selection that ends at the start of the next paragraph (as a
          // triple click makes) has an empty rectangle there, at the left
          // edge of a line that isn't selected. Place the button by the
          // selected text instead.
          const allRects = Array.from(window.getSelection().getRangeAt(0).getClientRects());
          const textRects = allRects.filter((rect) => rect.width > 0);
          const rects = textRects.length ? textRects : allRects;

          // Browsers can report no rectangles for a selection, leaving
          // nowhere to show the button.
          if (!rects.length) return;

          const firstRect = rects[0];
          const lastRect = rects[rects.length - 1];

          // A selection made by touch is reported by selectionchange, which
          // has no pointer position to place the button by.
          if (e.type === 'selectionchange') {
            button.showTouch(firstRect, lastRect, rects);
          } else if (e.clientY < firstRect.bottom && e.clientX - firstRect.right < firstRect.left - e.clientX) {
            button.showStart(firstRect.left, firstRect.top);
          } else {
            button.showEnd(lastRect.right, lastRect.bottom);
          }
        } else if (e.type === 'selectionchange' && !window.getSelection().isCollapsed) {
          // Text was selected somewhere else, such as in another post, with
          // no click to hide this post's button, which would be left behind
          // far from the selection.
          button.hide();
        }
      }, 1);
    };

    // A flashing post has a transform, which would become the quote button's
    // positioning context and make it jump when the animation ends.
    $(document.body).append($container);
    $(document).on('mouseup', handler);

    if ('ontouchstart' in window) {
      document.addEventListener('selectionchange', handler, false);
    }

    const prototype = Object.getPrototypeOf(this);
    // Resolve the parent hook at removal time so earlier and later extensions
    // both stay in the chain, even when they wrap an inherited method.
    this.onremove = function (...args) {
      try {
        return prototype.onremove.apply(this, args);
      } finally {
        document.removeEventListener('selectionchange', handler, false);
        $(document).off('mouseup', handler);
        button.hide();
        m.render($container[0], null);
        $container.remove();
      }
    };
  });
}
