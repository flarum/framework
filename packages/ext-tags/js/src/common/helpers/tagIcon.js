export default function tagIcon(tag, attrs = {}, settings = {}) {
  const hasIcon = tag && tag.icon();
  const { useColor = true } = settings;

  attrs.className = 'icon TagIcon '+ (attrs.className || '') + ' ' + (hasIcon ? tag.icon() : 'default');

  if (tag) {
    attrs.style = attrs.style || {};

    if (hasIcon) {
      attrs.style.color = useColor ? tag.color() : '';
    } else {
      attrs.style.backgroundColor = tag.color();
    }

  } else {
    attrs.className += ' untagged';
  }

  return hasIcon ? <i {...attrs}/> : <span {...attrs}/>;
}
