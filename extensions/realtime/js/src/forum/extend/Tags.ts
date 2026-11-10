import app from 'flarum/forum/app';
import IndexTyping from './Tags/IndexTyping';

/**
 * Integrations with flarum-tags. Each extender targets a tags component via its
 * registry path, so it lazily no-ops when flarum-tags isn't loaded (see core's
 * `extend`, which defers string-target extension to `reg.onLoad`).
 */
export default function Tags() {
  if (!!app.data['flarum-realtime.index-typing-indicator']) {
    IndexTyping();
  }
}
