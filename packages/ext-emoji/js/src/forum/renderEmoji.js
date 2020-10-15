/*global s9e*/

import twemoji from 'twemoji';

import { override } from 'flarum/extend';
import Post from 'flarum/models/Post';

import base from './cdn';

const options = {
  base,
  attributes: () => ({
    loading: 'lazy',
  }),
};

export default function renderEmoji() {
  override(Post.prototype, 'contentHtml', function(original) {
    const contentHtml = original();

    if (this.oldContentHtml !== contentHtml) {
      this.emojifiedContentHtml = twemoji.parse(contentHtml, options);
      this.oldContentHtml = contentHtml;
    }

    return this.emojifiedContentHtml;
  });

  override(s9e.TextFormatter, 'preview', (original, text, element) => {
    original(text, element);

    twemoji.parse(element, options);
  });
}
