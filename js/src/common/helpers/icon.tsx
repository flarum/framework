import * as Mithril from 'mithril';

/**
 * The `icon` helper displays an icon.
 *
 * @param fontClass The full icon class, prefix and the icon’s name.
 * @param attrs Any other attributes to apply.
 */
export default function icon(fontClass: string, attrs: Mithril.Attributes = {}): Mithril.Vnode {
  attrs.className = 'icon ' + fontClass + ' ' + (attrs.className || '');

  return <i {...attrs} />;
}
