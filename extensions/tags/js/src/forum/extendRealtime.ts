import app from 'flarum/forum/app';
import RealtimeExtend from 'ext:flarum/realtime/forum/extenders/Realtime';

export default function extendRealtime() {
  new RealtimeExtend().onDiscussionStreamEvent('taggedEvent').extend(app, { name: 'flarum-tags', exports: {} });
}
