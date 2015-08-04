import Dropdown from 'flarum/components/Dropdown';
import Button from 'flarum/components/Button';
import Separator from 'flarum/components/Separator';
import Group from 'flarum/models/Group';
import GroupBadge from 'flarum/components/GroupBadge';

function badgeForId(id) {
  const group = app.store.getById('groups', id);

  return group ? GroupBadge.component({group, label: null}) : '';
}

export default class PermissionDropdown extends Dropdown {
  static initProps(props) {
    super.initProps(props);

    props.className = 'PermissionDropdown';
    props.buttonClassName = 'Button Button--text';
  }

  view() {
    this.props.children = [];

    const groupIds = app.permissions[this.props.permission] || [];
    const everyone = groupIds.indexOf(Group.GUEST_ID) !== -1;
    const members = groupIds.indexOf(Group.MEMBER_ID) !== -1;
    const adminGroup = app.store.getById('groups', Group.ADMINISTRATOR_ID);

    if (everyone) {
      this.props.label = 'Everyone';
    } else if (members) {
      this.props.label = 'Members';
    } else {
      this.props.label = [
        badgeForId(Group.ADMINISTRATOR_ID),
        groupIds.map(badgeForId)
      ];
    }

    if (this.props.allowGuest) {
      this.props.children.push(
        Button.component({
          children: 'Everyone',
          icon: everyone ? 'check' : true,
          onclick: () => this.save([Group.GUEST_ID])
        })
      );
    }

    this.props.children.push(
      Button.component({
        children: 'Members',
        icon: members ? 'check' : true,
        onclick: () => this.save([Group.MEMBER_ID])
      }),

      Separator.component(),

      Button.component({
        children: [GroupBadge.component({group: adminGroup, label: null}), ' ', adminGroup.namePlural()],
        icon: !everyone && !members ? 'check' : true,
        disabled: !everyone && !members,
        onclick: e => {
          e.stopPropagation();
          this.save([]);
        }
      })
    );

    [].push.apply(
      this.props.children,
      app.store.all('groups')
        .filter(group => [Group.ADMINISTRATOR_ID, Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()) === -1)
        .map(group => Button.component({
          children: [GroupBadge.component({group, label: null}), ' ', group.namePlural()],
          icon: groupIds.indexOf(group.id()) !== -1 ? 'check' : true,
          onclick: (e) => {
            e.stopPropagation();
            this.toggle(group.id());
          }
        }))
    );

    return super.view();
  }

  save(groupIds) {
    const permission = this.props.permission;

    app.permissions[permission] = groupIds;

    app.request({
      method: 'POST',
      url: app.forum.attribute('apiUrl') + '/permission',
      data: {permission, groupIds}
    });
  }

  toggle(groupId) {
    const permission = this.props.permission;

    let groupIds = app.permissions[permission] || [];

    const index = groupIds.indexOf(groupId);

    if (index !== -1) {
      groupIds.splice(index, 1);
    } else {
      groupIds.push(groupId);
      groupIds = groupIds.filter(id => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(id) === -1);
    }

    this.save(groupIds);
  }
}
