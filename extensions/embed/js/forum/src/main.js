import { override, extend } from 'flarum/extend';
import app from 'flarum/app';
import Composer from 'flarum/components/Composer';
import PostStream from 'flarum/components/PostStream';
import ModalManager from 'flarum/components/ModalManager';
import AlertManager from 'flarum/components/AlertManager';
import PostMeta from 'flarum/components/PostMeta';
import mapRoutes from 'flarum/utils/mapRoutes';
import Pane from 'flarum/utils/Pane';
import Drawer from 'flarum/utils/Drawer';

import DiscussionPage from 'flarum/embed/components/DiscussionPage';

app.initializers.replace('boot', () => {
  m.route.mode = 'pathname';

  override(m, 'route', function(original, root, arg1, arg2, vdom) {
    if (arguments.length === 1) {

    } else if (arguments.length === 4 && typeof arg1 === 'string') {

    } else if (root.addEventListener || root.attachEvent) {
      root.href = vdom.attrs.href;
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

  app.pageInfo = m.prop();

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

  app.pane = new Pane(document.getElementById('app'));
  app.drawer = new Drawer();
  app.composer = m.mount(document.getElementById('composer'), Composer.component());
  app.modal = m.mount(document.getElementById('modal'), ModalManager.component());
  app.alerts = m.mount(document.getElementById('alerts'), AlertManager.component());

  app.viewingDiscussion = function(discussion) {
    return this.current instanceof DiscussionPage && this.current.discussion === discussion;
  };

  delete app.routes['index.filter'];
  app.routes['discussion'] = {path: '/embed/:id', component: DiscussionPage.component()};
  app.routes['discussion.near'] = {path: '/embed/:id/:near', component: DiscussionPage.component()};

  const basePath = app.forum.attribute('basePath');
  m.route.mode = 'pathname';
  m.route(
    document.getElementById('content'),
    basePath + '/',
    mapRoutes(app.routes, basePath)
  );

  if (m.route.param('hideFirstPost')) {
    extend(PostStream.prototype, 'view', vdom => {
      if (vdom.children[0].attrs['data-number'] === 1) {
        vdom.children.splice(0, 1);
      }
    });
  }

  // Initialize FastClick, which makes links and buttons much more responsive on
  // touch devices.
  $(() => {
    FastClick.attach(document.body);

    $('body').addClass('ontouchstart' in window ? 'touch' : 'no-touch');
  });

  app.booted = true;
});
