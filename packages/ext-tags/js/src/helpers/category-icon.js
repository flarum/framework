export default function categoryIcon(category, attrs) {
  attrs = attrs || {};

  if (category) {
    attrs.style = attrs.style || {};
    attrs.style.backgroundColor = category.color();
  } else {
    attrs.className = (attrs.className || '')+' uncategorized';
  }

  return m('span.icon.category-icon', attrs);
}
