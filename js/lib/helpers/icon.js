/**
 * The `icon` helper displays a FontAwesome icon. The fa-fw class is applied.
 *
 * @param {String} name The name of the icon class, without the `fa-` prefix.
 * @param {Object} attrs Any other attributes to apply.
 * @param {String} prefix The icon class prefix.
 * @return {Object}
 */
export default function icon(name, attrs = {}) {
  attrs.className = 'icon ' + name + ' ' + (attrs.className || '');

  return <i {...attrs}/>;
}
