export default function tagsLabel(tag, attrs) {
  attrs = attrs || {};
  attrs.style = attrs.style || {};
  attrs.className = attrs.className || '';

  var link = attrs.link;
  delete attrs.link;
  if (link) {
    attrs.href = app.route('tag', {tags: tag.slug()});
    attrs.config = m.route;
  }

  if (tag) {
    var color = tag.color();
    if (color) {
      attrs.style.backgroundColor = attrs.style.color = color;
      attrs.className += ' colored';
    }
  } else {
    attrs.className += ' untagged';
  }

  return m((link ? 'a' : 'span')+'.tag-label', attrs, m('span.tag-label-text', tag ? tag.name() : 'Untagged'));
}
