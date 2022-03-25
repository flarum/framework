import extract from 'flarum/common/utils/extract';
import tagLabel from './tagLabel';
import sortTags from '../utils/sortTags';
import type Tag from '../models/Tag';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';

export default function tagsLabel(tags: Tag[], attrs: ComponentAttrs = {}): Mithril.Children {
  const children = [];
  const link = extract(attrs, 'link');

  attrs.className = 'TagsLabel ' + (attrs.className || '');

  if (tags) {
    sortTags(tags).forEach((tag: Tag) => {
      if (tag || tags.length === 1) {
        children.push(tagLabel(tag, {link}));
      }
    });
  } else {
    children.push(tagLabel());
  }

  return <span {...attrs}>{children}</span>;
}
