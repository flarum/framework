import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Stream from 'flarum/common/utils/Stream';

/**
 * Wire up iframe-resizer's content-window side so the parent page can size the
 * iframe to fit the discussion, and reposition modals/composer relative to the
 * parent's scroll position.
 *
 * This is verified in a real iframe via the browser harness — it can't be
 * exercised in jsdom (no parent frame, no layout).
 */
export default function setupIframeResizer(): void {
  app.pageInfo = Stream({});

  const reposition = function (this: any) {
    const info = app.pageInfo();
    this.$().css('top', Math.max(0, (info.scrollTop ?? 0) - (info.offsetTop ?? 0)));
  };

  // Composer is code-split (and ModalManager may be in future), so extend both
  // by string path — the callback binds once each component's chunk loads.
  extend('flarum/common/components/ModalManager', 'show', reposition);
  extend('flarum/forum/components/Composer', 'show', reposition);

  window.iFrameResizer = {
    readyCallback() {
      window.parentIFrame.getPageInfo(app.pageInfo);
    },
  };
}
