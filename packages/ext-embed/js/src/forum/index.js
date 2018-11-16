import 'iframe-resizer/js/iframeResizer.contentWindow.js';

import { override, extend } from 'flarum/extend';
import app from 'flarum/app';
import ForumApplication from 'flarum/ForumApplication';
import Composer from 'flarum/components/Composer';
import PostStream from 'flarum/components/PostStream';
import ModalManager from 'flarum/components/ModalManager';
import AlertManager from 'flarum/components/AlertManager';
import PostMeta from 'flarum/components/PostMeta';
import mapRoutes from 'flarum/utils/mapRoutes';
import Pane from 'flarum/utils/Pane';
import Drawer from 'flarum/utils/Drawer';
import ScrollListener from 'flarum/utils/ScrollListener';

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

m.route.mode = 'pathname';

override(m, 'route', function(original, root, arg1, arg2, vdom) {
  if (arguments.length === 1) {

  } else if (arguments.length === 4 && typeof arg1 === 'string') {

  } else if (root.addEventListener || root.attachEvent) {
    root.href = vdom.attrs.href.replace('/embed', '/d');
    root.target = '_blank';

    // TODO: If href leads to a post within this discussion that we have
    // already loaded, then scroll to it?
    return;
  }

  return original.apply(this, Array.prototype.slice.call(arguments, 1));
});

// Trim the /embed prefix off of post permalinks
override(PostMeta.prototype, 'getPermalink', (original, post) => {
  return original(post).replace('/embed', '/d');
});

app.pageInfo = m.prop({});

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
    <a href={app.route.discussion(this.discussion).replace('/embed', '/d')} config={m.route}>
      {count} comment{count == 1 ? '' : 's'}
    </a>
  </h3>, 100);

  const props = items.get('controls').props;
  props.className = props.className.replace('App-primaryControl', '');
});

delete app.routes['index.filter'];
app.routes['discussion'] = {path: '/embed/:id', component: DiscussionPage.component()};
app.routes['discussion.near'] = {path: '/embed/:id/:near', component: DiscussionPage.component()};

