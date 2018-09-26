/*global s9e*/

import twemoji from 'twemoji/2/twemoji.npm';

import { override } from 'flarum/extend';
import Post from 'flarum/models/Post';

export default function renderEmoji() {
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
}
