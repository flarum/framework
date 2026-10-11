// An extension built under a directory that is itself called src/ (here
// tests/nested/src/), so its path has more than one src/ in it.
export default function init() {
  return import('./components/Lazy');
}
