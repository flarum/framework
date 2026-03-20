import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import RealtimeExtend from 'ext:flarum/realtime/forum/extenders/Realtime';
import addRealtimeTypingIndicator from './addRealtimeTypingIndicator';

const MESSAGE_CREATED_EVENT = 'Flarum\\Messages\\DialogMessage\\Event\\Created';

export default function extendRealtime() {
  new RealtimeExtend()
    .onUserChannelEvent(MESSAGE_CREATED_EVENT, (data: unknown) => {
      app.store.pushPayload(data as any);
      (app as any).dropdownDialogs?.refresh?.();
      (app as any).dialogs?.refresh?.();
    })
    .extend(app, { name: 'flarum-messages', exports: {} });

  // Bind the new message event on MessageStream so we can access
  // the stream state directly and push the message in without a full reload.
  extend('ext:flarum/messages/forum/components/MessageStream', 'oninit', function (this: any) {
    this.messageCreatedHandler = (data: unknown) => {
      const message = app.store.pushPayload(data as any) as any;

      if (message?.dialog?.()?.id() === this.attrs?.dialog?.id() && this.attrs.state.hasItems()) {
        this.attrs.state.push(message);
        setTimeout(() => this.scrollToBottom(), 50);
      }
    };
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'oncreate', function (this: any) {
    app.websocket_channels?.user?.bind(MESSAGE_CREATED_EVENT, this.messageCreatedHandler);
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'onremove', function (this: any) {
    app.websocket_channels?.user?.unbind(MESSAGE_CREATED_EVENT, this.messageCreatedHandler);
  });

  addRealtimeTypingIndicator();
}
