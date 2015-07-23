/*global twemoji, s9e*/

import { override } from 'flarum/extend';
import app from 'flarum/app';
import Post from 'flarum/models/Post';

app.initializers.add('emoji', () => {
  override(Post.prototype, 'contentHtml', original => {
    return twemoji.parse(original());
  });

  override(s9e.TextFormatter, 'preview', (original, content, elm) => {
    original(content, elm);
    twemoji.parse(elm);
  });
});
