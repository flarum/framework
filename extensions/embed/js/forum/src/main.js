import { override } from 'flarum/extend';
import app from 'flarum/app';
import Composer from 'flarum/components/Composer';
import ModalManager from 'flarum/components/ModalManager';
import AlertManager from 'flarum/components/AlertManager';

import DiscussionPage from 'embed/components/DiscussionPage';

app.initializers.add('boot', () => {
  override(m, 'route', function(original, root, arg1, arg2, vdom) {
    if (root.addEventListener || root.attachEvent) {
      root.href = vdom.attrs.href;
      root.target = '_blank';

      // TODO: If href leads to a post within this discussion that we have
      // already loaded, then scroll to it?
      return;
    }

    original.apply(this, arguments);
  });

  app.composer = m.mount(document.getElementById('composer'), Composer.component());
  app.modal = m.mount(document.getElementById('modal'), ModalManager.component());
  app.alerts = m.mount(document.getElementById('alerts'), AlertManager.component());

  m.mount(document.getElementById('content'), DiscussionPage.component());
});
