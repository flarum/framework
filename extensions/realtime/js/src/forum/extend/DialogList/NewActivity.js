import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import WebsocketUpdates from '../DiscussionList/WebsocketUpdates';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';

export default function () {
  // Attach to Headersecondary to update the new messages counter
  extend(HeaderSecondary.prototype, 'oninit', function () {
    this.releaseUpdates = function () {
      // Push new dialogs.
      this.websocketUpdates.release(this.attrs.state);
    };

    this.websocketEventPosted = function (data) {
      app.store.pushPayload(data);
      app.dialog?.load()?.then(() => m.redraw());
    };

    this.websocketUpdates = new WebsocketUpdates();
    this.releaseTimeout = this.websocketUpdates.getReleaseInterval();
  });

  extend(HeaderSecondary.prototype, 'oncreate', function () {
    app.websocket_channels.user?.bind('Flarum\\Messages\\DialogMessage\\Event\\Created', this.websocketEventPosted.bind(this));
  });

  extend(HeaderSecondary.prototype, 'onremove', function () {
    app.websocket_channels.user?.unbind('Flarum\\Messages\\DialogMessage\\Event\\Created');
  });

  // MessagesPage - update stream
  extend('ext:flarum/messages/forum/components/DialogSection', 'oninit', function () {
    this.websocketEventPosted = function (data) {
      // Push data
      const dialogMessage = app.store.pushPayload(data);

      // Current private dialog
      if (dialogMessage?.dialog()?.id() === this.attrs?.dialog?.id() && this.messages) {
        // Add message to current stream
        this.messages?.push?.(dialogMessage);

        // Scroll to bottom
        const messageStream = this.element?.querySelector('.MessageStream');
        if (messageStream) {
          messageStream.scrollTop = messageStream.scrollHeight;
        }
      }

      // Refresh
      app.dropdownDialogs?.refresh?.();
    };

    this.websocketUpdates = new WebsocketUpdates();
    this.releaseTimeout = this.websocketUpdates.getReleaseInterval();
  });

  extend('ext:flarum/messages/forum/components/DialogSection', 'oncreate', function () {
    app.websocket_channels.user?.bind('Flarum\\Messages\\DialogMessage\\Event\\Created', this.websocketEventPosted.bind(this));
  });

  extend('ext:flarum/messages/forum/components/DialogSection', 'onremove', function () {
    app.websocket_channels.user?.unbind('Flarum\\Messages\\DialogMessage\\Event\\Created');
  });
}
