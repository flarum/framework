import { Attributes, Vnode } from 'mithril';

/**
 * The `icon` helper displays an icon.
 *
 * @param {String} fontClass The full icon class, prefix and the icon’s name.
 * @param {Attributes} attrs Any other attributes to apply.
 * @return {Vnode}
 */
export default function icon(fontClass: string, attrs: Attributes = {}): Vnode {
  attrs.className = 'icon ' + fontClass + ' ' + (attrs.className || '');

  return <i {...attrs} />;
}
