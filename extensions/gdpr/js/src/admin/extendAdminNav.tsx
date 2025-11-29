import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import AdminNav from 'flarum/admin/components/AdminNav';
import LinkButton from 'flarum/common/components/LinkButton';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export default function extendAdminNav() {
  extend(AdminNav.prototype, 'items', function (items: ItemList<Mithril.Children>) {
    items.add(
      'gdpr',
      <LinkButton href={app.route('gdpr')} icon="fas fa-user-shield" title={app.translator.trans('flarum-gdpr.admin.nav.gdpr_title')}>
        {app.translator.trans('flarum-gdpr.admin.nav.gdpr_button')}
      </LinkButton>,
      48
    );
  });
}
