export default function categoryLabel(category) {
  return m('span.category-label', {style: {backgroundColor: category.color()}}, category.title());
}
