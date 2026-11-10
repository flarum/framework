import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import commonExtend from '../common/extend';
import AuditPage from './components/AuditPage';

export default [
  ...commonExtend,

  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-book',
        label: app.translator.trans('flarum-audit.admin.permissions.view'),
        permission: 'flarum-audit.view',
      }),
      'moderate'
    )
    .permission(
      () => ({
        icon: 'fas fa-book',
        label: [
          app.translator.trans('flarum-audit.admin.permissions.viewLimited'),
          ' ',
          <Button className="Button Button--audit-small" onclick={() => app.modal.show(() => import('./components/LimitedSettingsModal'))}>
            {app.translator.trans('flarum-audit.admin.limitedSettings.configure')}
          </Button>,
        ],
        permission: 'flarum-audit.viewLimited',
      }),
      'moderate'
    )
    .page(AuditPage),
];
