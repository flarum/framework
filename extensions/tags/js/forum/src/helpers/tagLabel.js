import extract from 'flarum/utils/extract';

export default function tagLabel(tag, attrs = {}) {
  attrs.style = attrs.style || {};
  attrs.className = 'TagLabel ' + (attrs.className || '');

  const link = extract(attrs, 'link');
  if (link) {
    attrs.href = app.route('tag', {tags: tag.slug()});
    attrs.config = m.route;
  }

  if (tag) {
    const color = tag.color();
    if (color) {
      attrs.style.backgroundColor = attrs.style.color = color;
      attrs.className += ' colored';
    }

    if (link) {
      attrs.title = tag.description() || '';
    }
  } else {
    attrs.className += ' untagged';
  }

  return (
    m((link ? 'a' : 'span'), attrs,
      <span className="TagLabel-text">
        {tag ? tag.name() : app.trans('tags.untagged')}
      </span>
    )
  );
}
