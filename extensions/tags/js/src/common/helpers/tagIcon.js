import classList from 'flarum/common/utils/classList';
import Icon from 'flarum/common/components/Icon';
import IconImage from 'flarum/common/components/IconImage';

export default function tagIcon(tag, attrs = {}, settings = {}) {
  const image = tag && tag.iconImageUrl();
  const hasIcon = tag && tag.icon();
  const { useColor = true } = settings;

  attrs.className = classList([attrs.className, 'text-colored', !hasIcon && !image && 'icon TagIcon']);

  if (tag && useColor) {
    attrs.style = attrs.style || {};
    attrs.style['--color'] = tag.color();
  } else if (!tag) {
    attrs.className += ' untagged';
  }

  // An uploaded image takes the icon's place.
  if (image) {
    return <IconImage {...attrs} className={classList('icon', attrs.className)} src={image} srcset={tag.iconImageSrcset()} />;
  }

  // Render through the Icon component so global icon behavior (e.g. a forced
  // FontAwesome style) applies to tag icons too. It adds the `icon` class.
  return hasIcon ? <Icon name={tag.icon()} {...attrs} /> : <span {...attrs} />;
}
