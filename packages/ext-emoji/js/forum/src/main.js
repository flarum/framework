/*global twemoji, s9e*/

import { override } from 'flarum/extend';
import app from 'flarum/app';
import Post from 'flarum/models/Post';
import Formatter from 'flarum/utils/Formatter';

app.initializers.add('emoji', () => {
  override(Post.prototype, 'contentHtml', original => {
    return twemoji.parse(original());
  });

  override(Formatter, 'format', (original, text) => {
    return twemoji.parse(original(text));
  });
});
