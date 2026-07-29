import Pusher, { Channel } from 'pusher-js';
import app from 'flarum/forum/app';
import Application from 'flarum/common/Application';
import { extend } from 'flarum/common/extend';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import IndexPage from 'flarum/forum/components/IndexPage';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import type { Children } from 'mithril';
import type Tag from 'ext:flarum/tags/common/models/Tag';

export type PusherBinding = {
  channels: {
    main: Channel;
    user: Channel | null;
  };
  pusher: Pusher;
};

app.initializers.add('flarum-pusher', () => {
  app.pushedUpdates = [];

  // The socket is created in `mount`, not here. Initializers run before
  // `app.forum` is populated (Application.boot sets `this.forum` after running
  // initializers), so reading `app.forum.attribute(...)` at this point throws
  // "Cannot read properties of undefined (reading 'attribute')" and aborts boot.
  // The `extend`s below only touch `app.pusher` lazily inside lifecycle/`.then`
  // callbacks that fire after boot, so they are safe to register here.
  extend(Application.prototype, 'mount' as any, function () {
    app.pusher = (async () => {
      const socket: Pusher = new Pusher(app.forum.attribute('pusherKey'), {
        authEndpoint: `${app.forum.attribute('apiUrl')}/pusher/auth`,
        cluster: app.forum.attribute('pusherCluster'),
        auth: {
          headers: {
            'X-CSRF-Token': app.session.csrfToken,
          },
        },
        httpHost: app.forum.attribute('pusherHostname'),
        wsHost: app.forum.attribute('pusherHostname'),
      });

      return {
        channels: {
          main: socket.subscribe('public'),
          user: app.session.user ? socket.subscribe(`private-user${app.session.user.id()}`) : null,
        },
        pusher: socket,
      };
    })();

    app.pusher.then((binding: PusherBinding) => {
      const channels = binding.channels;

      if (channels.user) {
        channels.user.bind('notification', () => {
          if (app.session.user) {
            app.session.user.pushAttributes({
              unreadNotificationCount: (app.session.user.unreadNotificationCount() ?? 0) + 1,
              newNotificationCount: (app.session.user.newNotificationCount() ?? 0) + 1,
            });
          }
          app.notifications.clear();
          m.redraw();
        });
      }
    });
  });

  // Discussion-list updates are wired on IndexPage (the stable page container)
  // and rendered via its `contentItems` ItemList. On 2.x DiscussionList no
  // longer exposes a vdom shape we can splice a banner into from `view`, and its
  // lifecycle is less stable than the page's — so we mirror flarum/realtime's
  // approach here rather than the 1.x DiscussionList vdom mutation.
  const newPostListHandler = (data: { tagIds: string[]; discussionId: number }) => {
    const params = app.discussions.getParams();

    // `getParams()` always returns a `filter` object (empty on the default
    // index), so test its contents — not truthiness — or we would bail on every
    // event. Mirrors flarum/realtime's guard.
    const hasFilters = Object.keys(params.filter ?? {}).length > 0;

    if (params.q || params.sort || hasFilters) return;

    if (params.tags) {
      const tag = app.store.getBy<Tag>('tags', 'slug', params.tags);
      const tagId = tag?.id();

      if (!tagId || !data.tagIds.includes(tagId)) return;
    }

    const id = String(data.discussionId);

    if ((!app.current.get('discussion') || id !== app.current.get('discussion').id()) && app.pushedUpdates.indexOf(id) === -1) {
      app.pushedUpdates.push(id);

      if (app.current.matches(IndexPage)) {
        app.setTitleCount(app.pushedUpdates.length);
      }

      m.redraw();
    }
  };

  extend(IndexPage.prototype, 'oncreate', function () {
    app.pusher.then((binding: PusherBinding) => {
      binding.pusher.bind('newPost', newPostListHandler);
    });
  });

  extend(IndexPage.prototype, 'onremove', function () {
    app.pusher.then((binding: PusherBinding) => {
      binding.pusher.unbind('newPost', newPostListHandler);
    });
  });

  extend(IndexPage.prototype, 'contentItems', function (this: IndexPage, items: ItemList<Children>) {
    const count = app.pushedUpdates?.length ?? 0;

    if (!count) return;

    items.add(
      'pusherNewActivity',
      <Button
        className="Button DiscussionList-update"
        aria-live="polite"
        onclick={() => {
          app.discussions.refresh().then(() => {
            app.pushedUpdates = [];
            app.setTitleCount(0);
            m.redraw();
          });
        }}
      >
        {app.translator.trans('flarum-pusher.forum.discussion_list.show_updates_text', { count })}
      </Button>,
      95
    );
  });

  extend(DiscussionPage.prototype, 'oncreate', function (this: DiscussionPage) {
    app.pusher.then((binding: PusherBinding) => {
      const pusher = binding.pusher;

      pusher.bind('newPost', (data: { discussionId: number }) => {
        const id = String(data.discussionId);
        const discussionId = this.discussion?.id();

        if (this.discussion && discussionId === id && this.stream) {
          const oldCount = this.discussion.commentCount() ?? 0;

          app.store.find('discussions', discussionId).then(() => {
            this.stream?.update().then(m.redraw);

            if (!document.hasFocus()) {
              app.setTitleCount(Math.max(0, (this.discussion?.commentCount() ?? 0) - oldCount));

              window.addEventListener('focus', () => app.setTitleCount(0), { once: true });
            }
          });
        }
      });
    });
  });

  extend(DiscussionPage.prototype, 'onremove', function () {
    app.pusher.then((binding: PusherBinding) => {
      binding.pusher.unbind('newPost');
    });
  });

  extend(IndexPage.prototype, 'actionItems', (items: ItemList<Children>) => {
    items.remove('refresh');
  });
});
