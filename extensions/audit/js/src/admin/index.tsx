import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import AuditPage from './components/AuditPage';
import addForumRoutes from './addForumRoutes';
import LimitedSettingsModal from './components/LimitedSettingsModal';

export { default as extend } from './extend';

app.initializers.add('flarum-audit', () => {
  addForumRoutes();

  app.extensionData
    .for('flarum-audit')
    .registerPermission(
      {
        icon: 'fas fa-book',
        label: app.translator.trans('flarum-audit.admin.permissions.view'),
        permission: 'flarum-audit.view',
      },
      'moderate'
    )
    .registerPermission(
      {
        icon: 'fas fa-book',
        label: [
          app.translator.trans('flarum-audit.admin.permissions.viewLimited'),
          ' ',
          <Button className="Button Button--audit-small" onclick={() => app.modal.show(LimitedSettingsModal)}>
            {app.translator.trans('flarum-audit.admin.limitedSettings.configure')}
          </Button>,
        ],
        permission: 'flarum-audit.viewLimited',
      },
      'moderate'
    )
    .registerPage(AuditPage);
});
