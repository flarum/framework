// Lazily imports a component which in turn lazily imports two others, the
// first of which statically imports the second (as DeckFilterModal does
// DeckPickerModal in flarum/deck).
export default function init() {
  return import('./components/Outer');
}
