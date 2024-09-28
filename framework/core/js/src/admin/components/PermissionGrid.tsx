import app from '../../admin/app';
import Component, { ComponentAttrs } from '../../common/Component';
import PermissionDropdown from './PermissionDropdown';
import SettingDropdown from './SettingDropdown';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
import Icon from '../../common/components/Icon';

export interface PermissionConfig {
  permission?: string;
  icon: string;
  label: Mithril.Children;
  id?: string;
  setting?: () => Mithril.Children;
  allowGuest?: boolean;
}

export interface PermissionSetting {
  setting: () => Mithril.Children;
  icon: string;
  label: Mithril.Children;
}

export type PermissionGridEntry = PermissionConfig | PermissionSetting;

export type PermissionType = 'view' | 'start' | 'reply' | 'moderate';

export interface ScopeItem {
  label: Mithril.Children;
  render: (permission: PermissionGridEntry) => Mithril.Children;
  onremove?: () => void;
}

export interface IPermissionGridAttrs extends ComponentAttrs {}

export default class PermissionGrid<CustomAttrs extends IPermissionGridAttrs = IPermissionGridAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const scopes = this.scopeItems().toArray();

    const permissionCells = (permission: PermissionGridEntry | { children: PermissionGridEntry[] }) => {
      return scopes.map((scope) => {
        // This indicates the "permission" is a permission category,
        // in which case we return an empty table cell.
        if ('children' in permission) {
          return <td></td>;
        }

        return <td>{scope.render(permission)}</td>;
      });
    };

    return (
      <table className="PermissionGrid">
        <thead>
          <tr>
            <th></th>
            {scopes.map((scope) => (
              <th>
                {scope.label}{' '}
                {!!scope.onremove && (
                  <Button icon="fas fa-times" className="Button Button--text PermissionGrid-removeScope" onclick={scope.onremove} />
                )}
              </th>
            ))}
            <th>{this.scopeControlItems().toArray()}</th>
          </tr>
        </thead>
        {this.permissionItems()
          .toArray()
          .map((section) => (
            <tbody>
              <tr className="PermissionGrid-section">
                <th>{section.label}</th>
                {permissionCells(section)}
                <td />
              </tr>
              {section.children.map((child) => (
                <tr className="PermissionGrid-child">
                  <th>
                    <Icon name={child.icon} />
                    {child.label}
                  </th>
                  {permissionCells(child)}
                  <td />
                </tr>
              ))}
            </tbody>
          ))}
      </table>
    );
  }

  permissionItems() {
    const items = new ItemList<{
      label: Mithril.Children;
      children: PermissionGridEntry[];
    }>();

    items.add(
      'view',
      {
        label: app.translator.trans('core.admin.permissions.read_heading'),
        children: this.viewItems().toArray(),
      },
      100
    );

    items.add(
      'start',
      {
        label: app.translator.trans('core.admin.permissions.create_heading'),
        children: this.startItems().toArray(),
      },
      90
    );

    items.add(
      'reply',
      {
        label: app.translator.trans('core.admin.permissions.participate_heading'),
        children: this.replyItems().toArray(),
      },
      80
    );

    items.add(
      'moderate',
      {
        label: app.translator.trans('core.admin.permissions.moderate_heading'),
        children: this.moderateItems().toArray(),
      },
      70
    );

    return items;
  }

  viewItems() {
    const items = new ItemList<PermissionGridEntry>();

    items.merge(app.registry.getAllPermissions('view'));

    return items;
  }

  startItems() {
    const items = new ItemList<PermissionGridEntry>();

    items.merge(app.registry.getAllPermissions('start'));

    return items;
  }

  replyItems() {
    const items = new ItemList<PermissionGridEntry>();

    items.merge(app.registry.getAllPermissions('reply'));

    return items;
  }

  moderateItems() {
    const items = new ItemList<PermissionGridEntry>();

    items.merge(app.registry.getAllPermissions('moderate'));

    return items;
  }

  scopeItems() {
    const items = new ItemList<ScopeItem>();

    items.add(
      'global',
      {
        label: app.translator.trans('core.admin.permissions.global_heading'),
        render: (item: PermissionGridEntry) => {
          if ('setting' in item) {
            return item.setting();
          } else if ('permission' in item) {
            return <PermissionDropdown permission={item.permission} allowGuest={item.allowGuest} />;
          }

          return null;
        },
      },
      100
    );

    return items;
  }

  scopeControlItems() {
    return new ItemList();
  }
}
