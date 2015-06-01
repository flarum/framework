import { extend } from 'flarum/extension-utils';
import ActionButton from 'flarum/components/action-button';
import CommentPost from 'flarum/components/comment-post';

export default function() {
  extend(CommentPost.prototype, 'actionItems', function(items) {
    var post = this.props.post;
    if (post.isHidden()) return;

    function insertMention(component, quote) {
      var mention = '@'+post.user().username()+'#'+post.number()+' ';

      // If the composer is empty, then assume we're starting a new reply.
      // In which case we don't want the user to have to confirm if they
      // close the composer straight away.
      if (!component.content()) {
        component.props.originalContent = mention;
      }

      component.editor.insertAtCursor((component.editor.getSelectionRange()[0] > 0 ? '\n\n' : '')+(quote ? '> '+mention+quote.trim().replace(/\n/g, '\n> ')+'\n\n' : mention));
    }

    items.add('reply',
      ActionButton.component({
        icon: 'reply',
        label: 'Reply',
        onclick: () => {
          var quote = window.getSelection().toString();

          var component = app.composer.component;
          if (component && component.props.post && component.props.post.discussion() === post.discussion()) {
            insertMention(component, quote);
          } else {
            post.discussion().replyAction().then(component => insertMention(component, quote));
          }
        }
      })
    );
  });
}
