import * as Mithril from 'mithril';

/**
 * The `icon` helper displays an icon.
 *
 * @param {String} fontClass The full icon class, prefix and the icon’s name.
 * @param {Mithril.Attributes} attrs Any other attributes to apply.
 * @return {Mithril.Vnode}
 */
export default function icon(fontClass: string, attrs: Mithril.Attributes = {}): Mithril.Vnode {
  attrs.className = 'icon ' + fontClass + ' ' + (attrs.className || '');

  return <i {...attrs} />;
}
