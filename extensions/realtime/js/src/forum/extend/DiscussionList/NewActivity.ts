import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import DiscussionList from 'flarum/forum/components/DiscussionList';
import IndexPage from 'flarum/forum/components/IndexPage';
import Button from 'flarum/common/components/Button';
import WebsocketUpdates from './WebsocketUpdates';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';

export default function (): void {
  extend(DiscussionList.prototype, 'oninit', function (this: any) {
    this.releaseUpdates = (): void => {
      this.websocketUpdates.release(this.attrs.state);
    };

    this.addDiscussion = (_returned: unknown, discussion: Discussion): void => {
      this.websocketUpdates.remove(discussion);

      if (app.current.matches(IndexPage)) {
        app.setTitleCount(this.websocketUpdates.length());
      }

      m.redraw();
    };

    this.websocketEventPosted = (data: unknown): void => {
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

        if ((this.websocketUpdates as WebsocketUpdates).has(discussion)) return;
        if ((app as any).discussions.getPages()[0]?.items[0]?.id() === discussion.id()) return;

        const pushOnIndex: boolean = !app.current.get('discussion');
        const pushOnView: boolean =
          discussion.id() === app.current.get('discussion')?.id() || subscribedTag || (discussion as any).subscription?.() === 'follow';

        if (pushOnIndex || pushOnView) {
          (this.websocketUpdates as WebsocketUpdates).push(discussion);

          if (app.current.matches(IndexPage)) {
            app.setTitleCount((this.websocketUpdates as WebsocketUpdates).length());
            m.redraw();
          }
        }
      }
    };

    this.websocketUpdates = new WebsocketUpdates();
    this.releaseTimeout = (this.websocketUpdates as WebsocketUpdates).getReleaseInterval();
  });

  extend(DiscussionList.prototype, 'oncreate', function (this: any) {
    app.websocket_channels.public?.bind('Flarum\\Discussion\\Event\\Started', this.websocketEventPosted.bind(this));
    app.websocket_channels.public?.bind('Flarum\\Post\\Event\\Posted', this.websocketEventPosted.bind(this));
    app.websocket_channels.user?.bind('Flarum\\Discussion\\Event\\Started', this.websocketEventPosted.bind(this));
    app.websocket_channels.user?.bind('Flarum\\Post\\Event\\Posted', this.websocketEventPosted.bind(this));
  });

  extend(DiscussionList.prototype, 'onremove', function () {
    app.websocket_channels.public?.unbind('Flarum\\Discussion\\Event\\Started');
    app.websocket_channels.public?.unbind('Flarum\\Post\\Event\\Posted');
    app.websocket_channels.user?.unbind('Flarum\\Discussion\\Event\\Started');
    app.websocket_channels.user?.unbind('Flarum\\Post\\Event\\Posted');
  });

  extend(DiscussionList.prototype, 'view', function (this: any, vdom: Mithril.Vnode | null) {
    if (!(this.websocketUpdates as WebsocketUpdates).isEmpty()) {
      const buttonLabel = (releaseTimeout: number): Mithril.Children =>
        (this.websocketUpdates as WebsocketUpdates).autoRelease()
          ? app.translator.trans('flarum-realtime.forum.push.discussion-list-new-activity-with-auto-release', {
              count: (this.websocketUpdates as WebsocketUpdates).length(),
              releaseTimeout,
            })
          : app.translator.trans('flarum-realtime.forum.push.discussion-list-new-activity', {
              count: (this.websocketUpdates as WebsocketUpdates).length(),
            });

      if (
        (this.websocketUpdates as WebsocketUpdates).length() &&
        typeof vdom === 'object' &&
        vdom &&
        'children' in vdom &&
        vdom.children instanceof Array
      ) {
        vdom.children.unshift(
          Button.component(
            {
              className: 'Button Button--block DiscussionList-update',
              'aria-live': 'polite',
              'aria-atomic': 'true',
              onclick: this.releaseUpdates.bind(this),
            },
            buttonLabel(this.releaseTimeout as number)
          )
        );

        (this.websocketUpdates as WebsocketUpdates).startTimer();

        (this.websocketUpdates as WebsocketUpdates).onTimer((second: number) => {
          if (second === 0) return this.releaseUpdates();
          this.$('.DiscussionList-update > .Button-label').text(extractText(buttonLabel(second)));
        });
      }
    }
  });

  extend(IndexPage.prototype, 'actionItems', (items) => {
    items.remove('refresh');
  });
}
