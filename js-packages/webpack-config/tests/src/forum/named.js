// A chunk name written by hand. Babel prints the comment with no space before
// the import path, which is what this fixture reproduces (and why Prettier,
// which would add the space, is told to leave the line alone).
export default function init() {
  // prettier-ignore
  return import(/* webpackChunkName: 'forum/components/Named', webpackMode: 'lazy-once' */'./components/Named');
}
