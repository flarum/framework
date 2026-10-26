// The two imports in different modules of one chunk: Split imports Picker, and
// a helper it statically imports imports Filter.
export default function init() {
  return import('./components/Split');
}
