import app from 'flarum/forum/app';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';

// ">!" at the start of a line opens a block spoiler unless an inline spoiler
// closes on that line with no ">" before it (an escaped character such as "\>"
// or "\<" does not count). This is Litedown's own rule.
const BLOCK_SPOILER_START = /^>!(?!(?:\\[^\n]|[^\\\n>])*?!<)/;

// A quote that starts with a block spoiler goes on the line after the mention:
// ">!" only starts a block spoiler at the start of a line. An inline spoiler
// stays on the mention line.
function quoteBody(quote) {
  const body = quote.trim().replace(/\n/g, '\n> ');

  return BLOCK_SPOILER_START.test(body) ? '\n> ' + body : body;
}

export async function insertMention(post, composer, quote) {
  await composer.editorReady();

  const mention = app.mentionFormats.mentionable('post').replacement(post) + ' ';

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
      (quote ? '> ' + mention + quoteBody(quote) + '\n\n' : mention),
    false
  );

  return composer;
}

export default function reply(post, quote) {
  if (app.composer.bodyMatches('flarum/forum/components/EditPostComposer') && app.composer.body.attrs.post.discussion() === post.discussion()) {
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
