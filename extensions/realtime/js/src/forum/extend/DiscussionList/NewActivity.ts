import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import IndexPage from 'flarum/forum/components/IndexPage';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import WebsocketUpdates from './WebsocketUpdates';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';
import RealtimeState from '../../RealtimeState';

export default function (): void {
  extend(IndexPage.prototype, 'oninit', function (this: any) {
    this._realtimeWebsocketUpdates = new WebsocketUpdates();
    this._realtimeReleaseTimeout = (this._realtimeWebsocketUpdates as WebsocketUpdates).getReleaseInterval();

    this._realtimeWebsocketEventPosted = (data: unknown): void => {
      const params = (app as any).discussions.getParams();
      const activeTag: any = params.tags ? (app.store as any).getBy('tags', 'slug', params.tags) : null;
      const noFilters: boolean = Object.keys(params.filter ?? {}).length === 0;

      if (!params.q && !params.sort && (activeTag || noFilters)) {
        const entity = app.store.pushPayload(data as Parameters<typeof app.store.pushPayload>[0]) as any;

        let discussion: Discussion | null = entity instanceof Discussion ? entity : null;

        if (!discussion && entity instanceof Post) {
          discussion = (entity as any).discussion();
        }

        if (!discussion) return;

        // Byobu private discussions guards.
        if (app.current.data.routeName === 'byobuPrivate' && !((discussion as any).recipientUsers?.() && (discussion as any).recipientGroups?.())) {
          return;
        }

        if (
          app.current.data.routeName === 'byobuPrivate' &&
          (discussion as any).recipientUsers?.()?.length === 0 &&
          (discussion as any).recipientGroups?.()?.length === 0
        ) {
          return;
        }

        if (app.current.data.routeName === 'byobuUserPrivate') return;
        if (app.current.data.routeName === 'user.discussions') return;

        // Tag-based filtering (flarum/tags).
        if (activeTag && (discussion as any).tags?.()) {
          const tagIds: string[] = (discussion as any).tags().map((tag: any): string => tag.id());
          if (!tagIds.includes(activeTag.id())) return;
        }

        if (
          (discussion as any).tags?.() &&
          (discussion as any).tags().find((tag: any) => {
            if (activeTag && activeTag.id() === tag.id()) return false;
            if (!activeTag && tag.isHidden?.()) return true;
            return tag.subscription?.() === 'hide';
          })
        ) {
          return;
        }

        // Subscription filtering (flarum/subscriptions).
        if ((discussion as any).subscription?.() === 'ignore') return;

        const subscribedTag = (discussion as any).tags?.()?.find((tag: any): boolean => {
          return tag.subscription?.() === 'lurk' || tag.subscription?.() === 'follow';
        });

        if (app.current.get('routeName') === 'following') {
          if ((params.filter?.['following-tag'] && !subscribedTag) || (discussion as any).subscription?.() !== 'follow') {
            return;
          }
        }

        const websocketUpdates = this._realtimeWebsocketUpdates as WebsocketUpdates;

        if (websocketUpdates.has(discussion)) return;
        if ((app as any).discussions.getPages()[0]?.items[0]?.id() === discussion.id()) return;

        const pushOnIndex: boolean = !app.current.get('discussion');
        const pushOnView: boolean =
          discussion.id() === app.current.get('discussion')?.id() || subscribedTag || (discussion as any).subscription?.() === 'follow';

        if (pushOnIndex || pushOnView) {
          websocketUpdates.push(discussion);
          app.setTitleCount(websocketUpdates.length());
          m.redraw();
        }
      }
    };
  });

  extend(IndexPage.prototype, 'oncreate', function (this: any) {
    // Bind handlers against the current channel objects. Extracted so it can
    // re-fire on reconnect — see flarum/framework#4597.
    const bindHandlers = (): void => {
      app.websocket_channels.public?.bind('Flarum\\Discussion\\Event\\Started', this._realtimeWebsocketEventPosted.bind(this));
      app.websocket_channels.public?.bind('Flarum\\Post\\Event\\Posted', this._realtimeWebsocketEventPosted.bind(this));
      app.websocket_channels.user?.bind('Flarum\\Discussion\\Event\\Started', this._realtimeWebsocketEventPosted.bind(this));
      app.websocket_channels.user?.bind('Flarum\\Post\\Event\\Posted', this._realtimeWebsocketEventPosted.bind(this));
    };

    bindHandlers();
    this._realtimeReconnectDisposer = RealtimeState.onChannelsReconnected(bindHandlers);
  });

  extend(IndexPage.prototype, 'onremove', function (this: any) {
    this._realtimeReconnectDisposer?.();
    this._realtimeReconnectDisposer = null;

    app.websocket_channels.public?.unbind('Flarum\\Discussion\\Event\\Started');
    app.websocket_channels.public?.unbind('Flarum\\Post\\Event\\Posted');
    app.websocket_channels.user?.unbind('Flarum\\Discussion\\Event\\Started');
    app.websocket_channels.user?.unbind('Flarum\\Post\\Event\\Posted');
  });

  extend(IndexPage.prototype, 'contentItems', function (this: any, items: ItemList<Mithril.Children>) {
    const websocketUpdates = this._realtimeWebsocketUpdates as WebsocketUpdates;

    if (!websocketUpdates || websocketUpdates.isEmpty()) return;

    const releaseUpdates = (): void => {
      websocketUpdates.release(app.discussions);
      app.setTitleCount(0);
      m.redraw();
    };

    const buttonLabel = (releaseTimeout: number): Mithril.Children =>
      websocketUpdates.autoRelease()
        ? app.translator.trans('flarum-realtime.forum.push.discussion-list-new-activity-with-auto-release', {
            count: websocketUpdates.length(),
            releaseTimeout,
          })
        : app.translator.trans('flarum-realtime.forum.push.discussion-list-new-activity', {
            count: websocketUpdates.length(),
          });

    websocketUpdates.startTimer();

    websocketUpdates.onTimer((second: number) => {
      if (second === 0) return releaseUpdates();
      this.$('.DiscussionList-update > .Button-label').text(extractText(buttonLabel(second)));
    });

    items.add(
      'realtimeNewActivity',
      Button.component(
        {
          className: 'Button DiscussionList-update',
          'aria-live': 'polite',
          'aria-atomic': 'true',
          onclick: releaseUpdates,
        },
        buttonLabel(this._realtimeReleaseTimeout as number)
      ),
      95
    );
  });

  extend(IndexPage.prototype, 'actionItems', (items) => {
    items.remove('refresh');
  });
}
