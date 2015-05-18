import { extend } from 'flarum/extension-utils';
import ActionButton from 'flarum/components/action-button';
import CommentPost from 'flarum/components/comment-post';

export default function() {
  extend(CommentPost.prototype, 'actionItems', function(items) {
    var post = this.props.post;
    if (post.isHidden()) return;

    items.add('reply',
      ActionButton.component({
        icon: 'reply',
        label: 'Reply',
        onclick: () => {
          var component = post.discussion().replyAction();
          if (component) {
            var quote = window.getSelection().toString();
            var mention = '@'+post.user().username()+'#'+post.number()+' ';
            component.editor.insertAtCursor((component.editor.value() ? '\n\n' : '')+(quote ? '> '+mention+quote+'\n\n' : mention));

            // If the composer is empty, then assume we're starting a new reply.
            // In which case we don't want the user to have to confirm if they
            // close the composer straight away.
            if (!component.content()) {
              component.props.originalContent = mention;
            }
          }
        }
      })
    );
  });
}
