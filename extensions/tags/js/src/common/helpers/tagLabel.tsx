import app from 'flarum/common/app';

import extract from 'flarum/common/utils/extract';
import Link from 'flarum/common/components/Link';
import tagIcon from './tagIcon';
import Tag from '../models/Tag';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';

export default function tagLabel(tag?: Tag, attrs: ComponentAttrs = {}): Mithril.Children {
  attrs.style = attrs.style || {};
  attrs.className = 'TagLabel ' + (attrs.className || '');

  const link = extract(attrs, 'link');
  const tagText = tag ? tag.name() : app.translator.trans('flarum-tags.lib.deleted_tag_text');

  if (tag) {
    const color = tag.color();
    if (color) {
      attrs.style['--tag-bg'] = color;
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

  const children = (
    <span className="TagLabel-text">
        {tag && tag.icon() && tagIcon(tag, {}, {useColor: false})} {tagText}
      </span>
  );

  if (link) {
    return <Link {...attrs}>{children}</Link>
  }

  return <span {...attrs}>{children}</span>;
}
