export default function categoryLabel(category, attrs) {
  attrs = attrs || {};

  if (category) {
    attrs.style = attrs.style || {};
    attrs.style.backgroundColor = attrs.style.color = category.color();
  } else {
    attrs.className = (attrs.className || '')+' uncategorized';
  }

  return m('span.category-label', attrs, m('span.category-label-text', category ? category.title() : 'Uncategorized'));
}
