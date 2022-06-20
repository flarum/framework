import { extend } from 'flarum/common/extend';
import BasicsPage from 'flarum/admin/components/BasicsPage';

export default function () {
  extend(BasicsPage.prototype, 'homePageItems', (items) => {
    items.add('tags', {
      path: '/tags',
      label: app.translator.trans('flarum-tags.admin.basics.tags_label'),
    });
  });
}
