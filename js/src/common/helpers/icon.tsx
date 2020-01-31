/**
 * The `icon` helper displays an icon.
 *
 * @param {String} fontClass The full icon class, prefix and the icon’s name.
 * @param {Object} attrs Any other attributes to apply.
 */
export default function icon(fontClass: string, attrs: any = {}) {
    attrs.className = 'icon ' + fontClass + ' ' + (attrs.className || '');

    return <i {...attrs} />;
}
