export default function categoryLabel(category, attrs) {
  attrs = attrs || {};

  if (category) {
    attrs.style = attrs.style || {};
    attrs.style[attrs.inverted ? 'color' : 'backgroundColor'] = category.color();
  } else {
    attrs.className = (attrs.className || '')+' uncategorized';
  }

  return m('span.category-label', attrs, category ? category.title() : 'Uncategorized');
}
