/*global twemoji, s9e*/

import { override } from 'flarum/extend';
import app from 'flarum/app';
import Post from 'flarum/models/Post';

app.initializers.add('emoji', () => {
  override(Post.prototype, 'contentHtml', function(original) {
    const contentHtml = original();

    if (this.oldContentHtml !== contentHtml) {
      this.emojifiedContentHtml = twemoji.parse(contentHtml);
      this.oldContentHtml = contentHtml;
    }

    return this.emojifiedContentHtml;
  });

  override(s9e.TextFormatter, 'preview', (original, text, element) => {
    original(text, element);

    twemoji.parse(element);
  });
});
