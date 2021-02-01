import extract from 'flarum/utils/extract';
import Link from 'flarum/components/Link';
import tagIcon from './tagIcon';

export default function tagLabel(tag, attrs = {}) {
  attrs.style = attrs.style || {};
  attrs.className = 'TagLabel ' + (attrs.className || '');

  const link = extract(attrs, 'link');
  const tagText = tag ? tag.name() : app.translator.trans('flarum-tags.lib.deleted_tag_text');

  if (tag) {
    const color = tag.color();
    if (color) {
      attrs.style.backgroundColor = attrs.style.color = color;
      attrs.className += ' colored';
    }

    if (link) {
      attrs.title = tag.description() || '';
      attrs.href = app.route('tag', {tags: tag.slug()});
    }

    if (tag.isChild()) {
      attrs.className += ' TagLabel--child';
    }
  } else {
    attrs.className += ' untagged';
  }

  return (
    m((link ? Link : 'span'), attrs,
      <span className="TagLabel-text">
        {tag && tag.icon() && tagIcon(tag, {}, {useColor: false})} {tagText}
      </span>
    )
  );
}
