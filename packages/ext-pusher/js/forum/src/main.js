/*global Pusher*/

import { extend } from 'flarum/extend';
import app from 'flarum/app';
import DiscussionList from 'flarum/components/DiscussionList';
import DiscussionPage from 'flarum/components/DiscussionPage';
import IndexPage from 'flarum/components/IndexPage';

app.initializers.add('pusher', () => {
  const loadPusher = m.deferred();

  $.getScript('//js.pusher.com/3.0/pusher.min.js', () => {
    loadPusher.resolve(new Pusher(app.forum.attribute('pusherKey')).subscribe('public'));
  });

  app.pusher = loadPusher.promise;
  app.pushedUpdates = [];

  extend(DiscussionList.prototype, 'config', function(x, isInitialized, context) {
    if (isInitialized) return;

    app.pusher.then(channel => {
      channel.bind('newPost', data => {
        const params = this.props.params;

        if (!params.q && !params.sort) {
          if (params.tags) {
            const tag = app.store.getBy('tags', 'slug', params.tags);

            if (data.tagIds.indexOf(tag.id()) === -1) return;
          }

          if ((!app.current.discussion || data.discussionId !== app.current.discussion.id()) && app.pushedUpdates.indexOf(data.discussionId) === -1) {
            app.pushedUpdates.push(data.discussionId);

            if (app.current instanceof IndexPage) {
              app.setTitleCount(app.pushedUpdates.length);
            }

            m.redraw();
          }
        }
      });

      context.onunload = () => channel.unbind();
    });
  });

  extend(DiscussionList.prototype, 'view', function(vdom) {
    if (app.pushedUpdates) {
      const count = app.pushedUpdates.length;

      if (count) {
        vdom.children.unshift(
          <button className="Button Button--block DiscussionList-update"
            onclick={() => {
              app.pushedUpdates = [];
              this.refresh();
            }}
            config={(element, isInitialized) => {
              if (!isInitialized) $(element).hide().fadeIn();
            }}>
            {app.trans('pusher.show_updated_discussions', {count})}
          </button>
        );
      }
    }
  });

  extend(DiscussionPage.prototype, 'config', function(x, isInitialized, context) {
    if (isInitialized) return;

    app.pusher.then(channel => {
      channel.bind('newPost', data => {
        if (this.discussion && this.discussion.id() === data.discussionId && this.stream) {
          const oldCount = this.discussion.commentsCount();

          app.store.find('discussions', this.discussion.id()).then(() => {
            this.stream.update();

            if (!document.hasFocus()) {
              app.setTitleCount(Math.max(0, this.discussion.commentsCount() - oldCount));

              $(window).one('focus', () => app.setTitleCount(0));
            }
          });
        }
      });

      context.onunload = () => channel.unbind();
    });
  });

  extend(IndexPage.prototype, 'actionItems', items => {
    delete items.refresh;
  });
});
