import tagLabel from 'flarum-tags/helpers/tag-label';

export default function tagsLabel(tags, attrs) {
  attrs = attrs || {};
  var children = [];

  var link = attrs.link;
  delete attrs.link;

  if (tags) {
    tags.forEach(tag => {
      children.push(tagLabel(tag, {link}));
    });
  } else {
    children.push(tagLabel());
  }

  return m('span.tags-label', attrs, children);
}
