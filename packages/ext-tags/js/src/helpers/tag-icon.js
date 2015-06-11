export default function tagIcon(tag, attrs) {
  attrs = attrs || {};

  if (tag) {
    attrs.style = attrs.style || {};
    attrs.style.backgroundColor = tag.color();
  } else {
    attrs.className = (attrs.className || '')+' untagged';
  }

  return m('span.icon.tag-icon', attrs);
}
