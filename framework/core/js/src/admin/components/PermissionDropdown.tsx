import app from '../../admin/app';
import Dropdown, { IDropdownAttrs } from '../../common/components/Dropdown';
import Button from '../../common/components/Button';
import Separator from '../../common/components/Separator';
import Group from '../../common/models/Group';
import Badge from '../../common/components/Badge';
import GroupBadge from '../../common/components/GroupBadge';
import Mithril from 'mithril';

function badgeForId(id: string) {
  const group = app.store.getById('groups', id);

  return !!group && <GroupBadge group={group} label={null} />;
}

function filterByRequiredPermissions(groupIds: string[], permission: string) {
  app.getRequiredPermissions(permission).forEach((required) => {
    const restrictToGroupIds = app.data.permissions[required] || [];

    if (restrictToGroupIds.indexOf(Group.GUEST_ID) !== -1) {
      // do nothing
    } else if (restrictToGroupIds.indexOf(Group.MEMBER_ID) !== -1) {
      groupIds = groupIds.filter((id) => id !== Group.GUEST_ID);
    } else if (groupIds.indexOf(Group.MEMBER_ID) !== -1) {
      groupIds = restrictToGroupIds;
    } else {
      groupIds = restrictToGroupIds.filter((id) => groupIds.indexOf(id) !== -1);
    }

    groupIds = filterByRequiredPermissions(groupIds, required);
  });

  return groupIds;
}

export interface IPermissionDropdownAttrs extends IDropdownAttrs {
  permission: string;
}

export default class PermissionDropdown<CustomAttrs extends IPermissionDropdownAttrs = IPermissionDropdownAttrs> extends Dropdown<CustomAttrs> {
  static initAttrs(attrs: IPermissionDropdownAttrs) {
    super.initAttrs(attrs);

    attrs.className = 'PermissionDropdown';
    attrs.buttonClassName = 'Button Button--text';
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const children = [];

    let groupIds = app.data.permissions[this.attrs.permission] || [];

    groupIds = filterByRequiredPermissions(groupIds, this.attrs.permission);

    const everyone = groupIds.includes(Group.GUEST_ID);
    const members = groupIds.includes(Group.MEMBER_ID);
    const adminGroup = app.store.getById<Group>('groups', Group.ADMINISTRATOR_ID)!;

    if (everyone) {
      this.attrs.label = <Badge icon="fas fa-globe" />;
    } else if (members) {
      this.attrs.label = <Badge icon="fas fa-user" />;
    } else {
      this.attrs.label = [badgeForId(Group.ADMINISTRATOR_ID), groupIds.map(badgeForId)];
    }

    if (this.showing) {
      if (this.attrs.allowGuest) {
        children.push(
          <Button icon={everyone ? 'fas fa-check' : true} onclick={() => this.save([Group.GUEST_ID])} disabled={this.isGroupDisabled(Group.GUEST_ID)}>
            <Badge icon="fas fa-globe" /> {app.translator.trans('core.admin.permissions_controls.everyone_button')}
          </Button>
        );
      }

      children.push(
        <Button icon={members ? 'fas fa-check' : true} onclick={() => this.save([Group.MEMBER_ID])} disabled={this.isGroupDisabled(Group.MEMBER_ID)}>
          <Badge icon="fas fa-user" /> {app.translator.trans('core.admin.permissions_controls.members_button')}
        </Button>,

        <Separator />,

        <Button
          icon={!everyone && !members ? 'fas fa-check' : true}
          disabled={!everyone && !members}
          onclick={(e: MouseEvent) => {
            if (e.shiftKey) e.stopPropagation();
            this.save([]);
          }}
        >
          {badgeForId(adminGroup.id()!)} {adminGroup.namePlural()}
        </Button>
      );

      // These groups are defined above, appearing first in the list.
      const excludedGroups = [Group.ADMINISTRATOR_ID, Group.GUEST_ID, Group.MEMBER_ID];

      const groupButtons = app.store
        .all<Group>('groups')
        .filter((group) => !excludedGroups.includes(group.id()!))
        .map((group) => (
          <Button
            icon={groupIds.includes(group.id()!) ? 'fas fa-check' : true}
            onclick={(e: MouseEvent) => {
              if (e.shiftKey) e.stopPropagation();
              this.toggle(group.id()!);
            }}
            disabled={this.isGroupDisabled(group.id()!) && this.isGroupDisabled(Group.MEMBER_ID) && this.isGroupDisabled(Group.GUEST_ID)}
          >
            {badgeForId(group.id()!)} {group.namePlural()}
          </Button>
        ));

      children.push(...groupButtons);
    }

    return super.view({ ...vnode, children });
  }

  save(groupIds: string[]) {
    const permission = this.attrs.permission;

    app.data.permissions[permission] = groupIds;

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/permission',
      body: { permission, groupIds },
    });
  }

  toggle(groupId: string) {
    const permission = this.attrs.permission;

    let groupIds = app.data.permissions[permission] || [];

    const index = groupIds.indexOf(groupId);

    if (index !== -1) {
      groupIds.splice(index, 1);
    } else {
      groupIds.push(groupId);
      groupIds = groupIds.filter((id) => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(id) === -1);
    }

    this.save(groupIds);
  }

  isGroupDisabled(id: string) {
    return !filterByRequiredPermissions([id], this.attrs.permission).includes(id);
  }
}
