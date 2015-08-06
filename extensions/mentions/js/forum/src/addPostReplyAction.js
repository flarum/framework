import { extend } from 'flarum/extend';
import Button from 'flarum/components/Button';
import CommentPost from 'flarum/components/CommentPost';
import DiscussionControls from 'flarum/utils/DiscussionControls';

export default function() {
  extend(CommentPost.prototype, 'actionItems', function(items) {
    const post = this.props.post;

    if (post.isHidden() || (app.session.user && !post.discussion().canReply())) return;

    function insertMention(component, quote) {
      const mention = '@' + post.user().username() + '#' + post.id() + ' ';

      // If the composer is empty, then assume we're starting a new reply.
      // In which case we don't want the user to have to confirm if they
      // close the composer straight away.
      if (!component.content()) {
        component.props.originalContent = mention;
      }

      component.editor.insertAtCursor(
        (component.editor.getSelectionRange()[0] > 0 ? '\n\n' : '') +
        (quote
          ? '> ' + mention + quote.trim().replace(/\n/g, '\n> ') + '\n\n'
          : mention)
      );
    }

    items.add('reply',
      Button.component({
        className: 'Button Button--text',
        children: app.trans('mentions.reply_link'),
        onclick: () => {
          const quote = window.getSelection().toString();

          const component = app.composer.component;
          if (component && component.props.post && component.props.post.discussion() === post.discussion()) {
            insertMention(component, quote);
          } else {
            DiscussionControls.replyAction.call(post.discussion())
              .then(newComponent => insertMention(newComponent, quote));
          }
        }
      })
    );
  });
}
