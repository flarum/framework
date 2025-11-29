import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import User from 'flarum/common/models/User';
import Badge from 'flarum/common/components/Badge';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export default function () {
  extend(User.prototype, 'badges', function (badges: ItemList<Mithril.Children>) {
    if (this.anonymized()) {
      badges.add(
        'anonymized',
        <Badge label={app.translator.trans('flarum-gdpr.forum.badges.anonymized_user')} icon="fas fa-user-secret" type="anonymized" />
      );
    }
  });
}
