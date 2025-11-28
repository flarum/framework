import Flagged from './Post/Flagged';

export default function () {
  if ('flarum-flags' in flarum.extensions) {
    Flagged();
  }
}
