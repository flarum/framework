export default function categoryLabel(category) {
  return m('span.category-label', {style: {color: category.color()}}, category.title());
}
