import twemoji from '@twemoji/api';

import { override } from 'flarum/common/extend';
import Post from 'flarum/common/models/Post';

import cdn from '../common/cdn';

function options() {
  return {
    base: cdn(),
    attributes: () => ({
      loading: 'lazy',
    }),
  };
}

/**
 * Parses an HTML string into a `<body>` node containing the HTML content.
 *
 * Vanilla JS implementation of jQuery's `$.parseHTML()`,
 * sourced from http://youmightnotneedjquery.com/
 */
function parseHTML(str: string): HTMLBodyElement {
  const tmp = document.implementation.createHTMLDocument();
  tmp.body.innerHTML = str;

  return tmp.body as HTMLBodyElement;
}

/**
 * Parses emoji inside `root` into Twemoji images, except within code.
 *
 * Twemoji's list of elements to skip is not configurable, so code is
 * emojified like everything else and then put back as the text it was,
 * so that it is displayed and copied as typed.
 */
function emojify<T extends HTMLElement>(root: T): T {
  twemoji.parse(root, options());

  root.querySelectorAll<HTMLImageElement>('pre img.emoji, code img.emoji').forEach((img) => {
    const parent = img.parentNode!;

    img.replaceWith(document.createTextNode(img.alt));
    parent.normalize();
  });

  return root;
}

export default function renderEmoji(): void {
  (override as any)(Post.prototype, 'contentHtml', function (this: any, original: () => string) {
    const contentHtml = original();

    if (this.oldContentHtml !== contentHtml) {
      // We need to parse the HTML string into a DOM node, then give it to Twemoji.
      //
      // This prevents some issues with the default find-replace that would be performed
      // on a string passed to `Twemoji.parse()`.
      //
      // The parse function can only handle a single DOM node provided, so we need to
      // wrap it in a node. In our `parseHTML` implementation, we wrap it in a `<body>`
      // element. This gets stripped below.
      //
      // See https://github.com/flarum/core/issues/2958
      const emojifiedDom = emojify(parseHTML(contentHtml));

      // Steal the HTML string inside the emojified DOM `<body>` tag.
      this.emojifiedContentHtml = emojifiedDom.innerHTML;

      this.oldContentHtml = contentHtml;
    }

    return this.emojifiedContentHtml;
  });

  (override as any)(s9e.TextFormatter, 'preview', (original: (text: string, element: HTMLElement) => void, text: string, element: HTMLElement) => {
    original(text, element);

    emojify(element);
  });
}
