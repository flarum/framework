import app from 'flarum/forum/app';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import EditPostComposer from 'flarum/forum/components/EditPostComposer';
import getMentionText from './getMentionText';

export function insertMention(post, composer, quote) {
  return new Promise((resolve) => {
    const user = post.user();
    const mention = getMentionText(user, post.id()) + ' ';

    // If the composer is empty, then assume we're starting a new reply.
    // In which case we don't want the user to have to confirm if they
    // close the composer straight away.
    if (!composer.fields.content()) {
      composer.body.attrs.originalContent = mention;
    }

    const cursorPosition = composer.editor.getSelectionRange()[0];
    const preceding = composer.fields.content().slice(0, cursorPosition);
    const precedingNewlines = preceding.length == 0 ? 0 : 3 - preceding.match(/(\n{0,2})$/)[0].length;

    composer.editor.insertAtCursor(
      Array(precedingNewlines).join('\n') + // Insert up to two newlines, depending on preceding whitespace
        (quote ? '> ' + mention + quote.trim().replace(/\n/g, '\n> ') + '\n\n' : mention),
      false
    );
    return resolve(composer);
  });
}

export default function reply(post, quote) {
  if (app.composer.bodyMatches(EditPostComposer) && app.composer.body.attrs.post.discussion() === post.discussion()) {
    // If we're already editing a post in the discussion of post we're quoting,
    // insert the mention directly.
    return insertMention(post, app.composer, quote);
  } else {
    // The default "Reply" action behavior will only open a new composer if
    // necessary, but it will always be a ReplyComposer, hence the exceptional
    // case above.
    return DiscussionControls.replyAction.call(post.discussion()).then((composer) => insertMention(post, composer, quote));
  }
}
