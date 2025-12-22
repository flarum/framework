import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import UserControls from 'flarum/forum/utils/UserControls';
import ItemList from 'flarum/common/utils/ItemList';
import User from 'flarum/common/models/User';
import Button from 'flarum/common/components/Button';
import RequestDataExportModal from '../../common/components/RequestDataExportModal';
import type Mithril from 'mithril';
import DeleteUserModal from '../components/DeleteUserModal';

export default function extendUserControls() {
  extend(UserControls, 'moderationControls', function (items: ItemList<Mithril.Children>, user: User) {
    if (user.canModerateExports()) {
      items.add(
        'gdpr-export',
        <Button icon="fas fa-file-export" onclick={() => app.modal.show(RequestDataExportModal as any, { user })}>
          {app.translator.trans('flarum-gdpr.forum.settings.export_data_button')}
        </Button>
      );
    }
  });

  extend(UserControls, 'destructiveControls', function (items: ItemList<Mithril.Children>, user: User) {
    items.remove('delete');

    if (user.canDelete()) {
      items.add(
        'gdpr-erase',
        <Button icon="fas fa-eraser" onclick={() => app.modal.show(DeleteUserModal as any, { user })}>
          {app.translator.trans('flarum-gdpr.forum.delete_user.delete_button')}
        </Button>
      );
    }
  });
}
