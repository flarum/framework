import tagLabel from 'flarum-tags/helpers/tag-label';
import sortTags from 'flarum-tags/utils/sort-tags';

export default function tagsLabel(tags, attrs) {
  attrs = attrs || {};
  var children = [];

  var link = attrs.link;
  delete attrs.link;

  if (tags) {
    sortTags(tags).forEach(tag => {
      if (tag || tags.length === 1) {
        children.push(tagLabel(tag, {link}));
      }
    });
  } else {
    children.push(tagLabel());
  }

  return m('span.tags-label', attrs, children);
}
