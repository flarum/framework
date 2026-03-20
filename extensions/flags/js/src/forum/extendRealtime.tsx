import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import RealtimeExtend from 'ext:flarum/realtime/forum/extenders/Realtime';

export default function extendRealtime() {
  new RealtimeExtend()
    .onUserChannelEvent('flagged', (data: unknown) => {
      app.session.user = app.store.pushPayload(data as any) as any;
      app.forum.pushAttributes({ flagCount: app.session.user?.attribute('newFlagCount') });
      app.flags.clear();
      m.redraw();
    })
    .extend(app, { name: 'flarum-flags', exports: {} });

  extend(DiscussionPage.prototype, 'oncreate', function (this: any) {
    app.websocket_channels.user?.bind('flaggedStream', (data: unknown) => {
      const discussion = app.store.pushPayload(data as any) as any;

      if (discussion?.id() === this.discussion?.id() && this.stream) {
        app.store
          .find('posts', { filter: { discussion: discussion.id() }, include: 'flags,flags.user' })
          .then(() => this.stream.update())
          .then(() => m.redraw());
      }
    });
  });

  extend(DiscussionPage.prototype, 'onremove', function (this: any) {
    app.websocket_channels.user?.unbind('flaggedStream');
  });
}
