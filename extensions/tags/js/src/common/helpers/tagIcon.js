import classList from 'flarum/common/utils/classList';

export default function tagIcon(tag, attrs = {}, settings = {}) {
  const hasIcon = tag && tag.icon();
  const { useColor = true } = settings;

  attrs.className = classList([attrs.className, 'icon', hasIcon ? tag.icon() : 'TagIcon']);

  if (tag && useColor) {
    attrs.style = attrs.style || {};
    attrs.style['--color'] = tag.color();

    if (hasIcon) {
      attrs.style.color = tag.color();
    }
  } else if (!tag) {
    attrs.className += ' untagged';
  }

  return hasIcon ? <i {...attrs} /> : <span {...attrs} />;
}
