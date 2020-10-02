import 'iframe-resizer/js/iframeResizer.contentWindow.js';

import { override, extend } from 'flarum/extend';
import app from 'flarum/app';
import Stream from 'flarum/utils/Stream';
import ForumApplication from 'flarum/ForumApplication';
import Composer from 'flarum/components/Composer';
import PostStream from 'flarum/components/PostStream';
import ModalManager from 'flarum/components/ModalManager';
import PostMeta from 'flarum/components/PostMeta';

import DiscussionPage from 'flarum/components/DiscussionPage';

extend(ForumApplication.prototype, 'mount', function() {
  if (m.route.param('hideFirstPost')) {
    extend(PostStream.prototype, 'view', vdom => {
      if (vdom.children[0].attrs['data-number'] === 1) {
        vdom.children.splice(0, 1);
      }
    });
  }
});

override(m.route.Link, 'view', function (original, vnode) {
  vnode.attrs.href = vnode.attrs.href.replace('/embed', '/d');
  vnode.attrs.target = '_blank';
  // TODO: If href leads to a post within this discussion that we have
  // already loaded, then scroll to it?
  return original(vnode);
});

// Trim the /embed prefix off of post permalinks
override(PostMeta.prototype, 'getPermalink', (original, post) => {
  return original(post).replace('/embed', '/d');
});

app.pageInfo = Stream({});

const reposition = function() {
  const info = app.pageInfo();
  this.$().css('top', Math.max(0, info.scrollTop - info.offsetTop));
};

extend(ModalManager.prototype, 'show', reposition);
extend(Composer.prototype, 'show', reposition);

window.iFrameResizer = {
  readyCallback: function() {
    window.parentIFrame.getPageInfo(app.pageInfo);
  }
};

extend(PostStream.prototype, 'goToNumber', function(promise, number) {
  if (number === 'reply' && 'parentIFrame' in window && app.composer.isFullScreen()) {
    const itemTop = this.$('.PostStream-item:last').offset().top;
    window.parentIFrame.scrollToOffset(0, itemTop);
  }
});

extend(DiscussionPage.prototype, 'sidebarItems', function(items) {
  items.remove('scrubber');

  const count = this.discussion.replyCount();

  items.add('replies', <h3>
    <a route={app.route.discussion(this.discussion).replace('/embed', '/d')}>
      {count} comment{count == 1 ? '' : 's'}
    </a>
  </h3>, 100);

  const attrs = items.get('controls').attrs;
  attrs.className = attrs.className.replace('App-primaryControl', '');
});

app.routes['discussion'] = {path: '/embed/:id', component: DiscussionPage};
app.routes['discussion.near'] = {path: '/embed/:id/:near', component: DiscussionPage};

