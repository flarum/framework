import { extend } from 'flarum/extend';
import AdminNav from 'flarum/components/AdminNav';
import AdminLinkButton from 'flarum/components/AdminLinkButton';

import TagsPage from 'tags/components/TagsPage';

export default function() {
  app.routes.tags = {path: '/tags', component: TagsPage.component()};

  app.extensionSettings.tags = () => m.route(app.route('tags'));

  extend(AdminNav.prototype, 'items', items => {
    items.add('tags', AdminLinkButton.component({
      href: app.route('tags'),
      icon: 'tags',
      children: 'Tags',
      description: 'Manage the list of tags available to organise discussions with.'
    }));
  });
}
