// The same shape with the imports the other way round, so the result cannot
// depend on which import the plugin happens to see first.
export default function init() {
  return import('./components/OuterReversed');
}
