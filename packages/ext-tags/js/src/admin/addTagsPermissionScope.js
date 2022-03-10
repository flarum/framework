import { extend, override } from 'flarum/extend';
import PermissionGrid from 'flarum/components/PermissionGrid';
import PermissionDropdown from 'flarum/components/PermissionDropdown';
import Dropdown from 'flarum/components/Dropdown';
import Button from 'flarum/components/Button';
import LoadingIndicator from 'flarum/components/LoadingIndicator';

import tagLabel from '../common/helpers/tagLabel';
import tagIcon from '../common/helpers/tagIcon';
import sortTags from '../common/utils/sortTags';

export default function() {
  extend(PermissionGrid.prototype, 'oninit', function () {
    this.loading = true;
  })

  extend(PermissionGrid.prototype, 'oncreate', function () {
    app.store.find('tags').then(() => {
      this.loading = false;

      m.redraw();
    });
  });

  override(PermissionGrid.prototype, 'view', function (original, vnode) {
    if (this.loading) {
      return <LoadingIndicator />;
    }

    return original(vnode);
  })

  override(app, 'getRequiredPermissions', (original, permission) => {
    const tagPrefix = permission.match(/^tag\d+\./);

    if (tagPrefix) {
      const globalPermission = permission.substr(tagPrefix[0].length);

      const required = original(globalPermission);

      return required.map(required => tagPrefix[0] + required);
    }

    return original(permission);
  });

  extend(PermissionGrid.prototype, 'scopeItems', items => {
    sortTags(app.store.all('tags'))
      .filter(tag => tag.isRestricted())
      .forEach(tag => items.add('tag' + tag.id(), {
        label: tagLabel(tag),
        onremove: () => tag.save({isRestricted: false}),
        render: item => {
          if (item.permission === 'viewForum'
            || item.permission === 'startDiscussion'
            || (item.permission && item.permission.indexOf('discussion.') === 0 && item.tagScoped !== false)
            || item.tagScoped) {
            return PermissionDropdown.component({
              permission: 'tag' + tag.id() + '.' + item.permission,
              allowGuest: item.allowGuest
            });
          }

          return '';
        }
      }));
  });

  extend(PermissionGrid.prototype, 'scopeControlItems', items => {
    const tags = sortTags(app.store.all('tags').filter(tag => !tag.isRestricted()));

    if (tags.length) {
      items.add('tag', <Dropdown className='Dropdown--restrictByTag' buttonClassName='Button Button--text' label={app.translator.trans('flarum-tags.admin.permissions.restrict_by_tag_heading')} icon='fas fa-plus' caretIcon={null}>
        {tags.map(tag => <Button icon={true} onclick={() => tag.save({ isRestricted: true })}>
          {[tagIcon(tag, { className: 'Button-icon' }), ' ', tag.name()]}
        </Button>)}
      </Dropdown>);
    }
  });
}
