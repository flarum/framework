import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Application from 'flarum/common/Application';

/**
 * Remove the first post from a rendered PostStream vdom. Used when the
 * `hideFirstPost` route param is set, so the embed can show only the replies.
 */
export function hideFirstPost(vdom: { children: any[] }): void {
  if (vdom.children[0]?.attrs?.['data-number'] === 1) {
    vdom.children.splice(0, 1);
  }
}

/**
 * When replying inside the embed iframe, the iframe itself doesn't scroll
 * (iframe-resizer expands it to fit its content), so ask the parent frame to
 * scroll to the reply position instead. No-op outside an iframe or when the
 * composer isn't full screen.
 */
export function scrollParentToReply(offsetTop: number): void {
  if ('parentIFrame' in window && app.composer.isFullScreen()) {
    window.parentIFrame.scrollToOffset(0, offsetTop);
  }
}

export default function extendPostStream(): void {
  // `hideFirstPost` must be read at mount time, so register the PostStream view
  // extension from within the app mount. PostStream is code-split, so extend it
  // by string path — the callback runs once the component's chunk has loaded.
  extend(Application.prototype, 'mount' as any, function () {
    if (m.route.param('hideFirstPost')) {
      extend('flarum/forum/components/PostStream', 'view', (vdom: any) => hideFirstPost(vdom));
    }
  });

  // 2.x routes reply navigation through PostStream.scrollToItem (the component
  // method that still has DOM access and receives the `reply` flag), rather
  // than the old PostStream.goToNumber. PostStream is lazily loaded, so use the
  // string-path form of extend() to defer until the chunk is available.
  extend('flarum/forum/components/PostStream', 'scrollToItem', function (this: any, _result: unknown, ...args: unknown[]) {
    // scrollToItem($item, animate, force, reply) — the 4th arg flags a reply.
    const reply = args[3];

    if (reply) {
      const $last = this.$('.PostStream-item:last-child');

      if ($last.length) {
        scrollParentToReply($last.offset().top);
      }
    }
  });
}
