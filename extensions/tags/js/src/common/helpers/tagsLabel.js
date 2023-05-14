import extract from 'flarum/common/utils/extract';
import tagLabel from './tagLabel';
import sortTags from '../utils/sortTags';
import classList from 'flarum/common/utils/classList';

export default function tagsLabel(tags, attrs = {}) {
  const children = [];
  const { link, ...otherAttrs } = attrs;

  otherAttrs.className = classList('TagsLabel', otherAttrs.className);

  if (tags) {
    sortTags(tags).forEach((tag) => {
      if (tag || tags.length === 1) {
        children.push(tagLabel(tag, { link }));
      }
    });
  } else {
    children.push(tagLabel());
  }

  return <span {...otherAttrs}>{children}</span>;
}
