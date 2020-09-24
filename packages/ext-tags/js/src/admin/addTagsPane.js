import { extend } from 'flarum/extend';
import AdminNav from 'flarum/components/AdminNav';
import AdminLinkButton from 'flarum/components/AdminLinkButton';

import TagsPage from './components/TagsPage';

export default function() {
  app.routes.tags = {path: '/tags', component: TagsPage};

  app.extensionSettings['flarum-tags'] = () => m.route.set(app.route('tags'));

  extend(AdminNav.prototype, 'items', items => {
    items.add('tags', AdminLinkButton.component({
      href: app.route('tags'),
      icon: 'fas fa-tags',
      description: app.translator.trans('flarum-tags.admin.nav.tags_text')
    }, app.translator.trans('flarum-tags.admin.nav.tags_button')));
  });
}
