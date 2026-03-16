import sortable from 'sortablejs';

import app from '../../admin/app';
import Component, { ComponentAttrs } from '../../common/Component';
import GroupBadge from '../../common/components/GroupBadge';
import Icon from '../../common/components/Icon';
import EditGroupModal from './EditGroupModal';
import sortGroups from '../../common/utils/sortGroups';

import type Group from '../../common/models/Group';
import type Mithril from 'mithril';

export interface IGroupBarAttrs extends ComponentAttrs {
  groups: Group[];
}

/**
 * A reusable component for displaying a list of groups in the admin interface,
 * with the ability to edit and reorder them.
 *
 * @property {Group[]} groups Required. Groups to display.
 *
 * @example
 * ```ts
 * const availableGroups = app.store
 *   .all<Group>('groups')
 *   .filter((group) => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()!) === -1);
 *
 * <GroupBar groups={availableGroups} />
 * ```
 */
export default class GroupBar<CustomAttrs extends IGroupBarAttrs = IGroupBarAttrs> extends Component<CustomAttrs> {
  groups: Group[] = [];

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.groups = sortGroups(this.attrs.groups);
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);
    this.groups = sortGroups(this.attrs.groups);
  }

  view(): JSX.Element {
    return (
      <div className="GroupBar" oncreate={this.onGroupBarCreate.bind(this)}>
        {this.groups.map((group) => (
          <button className="Button Group" type="button" data-id={group.id()} onclick={() => app.modal.show(EditGroupModal, { group })}>
            <GroupBadge group={group} className="Group-icon" label={null} />
            <span className="Group-name">{group.namePlural()}</span>
          </button>
        ))}
        <button className="Button Group Group--add" type="button" onclick={() => app.modal.show(EditGroupModal)}>
          <Icon name="fas fa-plus" className="Group-icon" />
          <span className="Group-name">{app.translator.trans('core.admin.permissions.new_group_button')}</span>
        </button>
      </div>
    );
  }

  onGroupBarCreate(vnode: Mithril.VnodeDOM) {
    sortable.create(vnode.dom as HTMLElement, {
      group: 'groups',
      delay: 50,
      delayOnTouchOnly: true,
      touchStartThreshold: 5,
      animation: 150,
      swapThreshold: 0.65,
      dragClass: 'Group-Sortable-Dragging',
      ghostClass: 'Group-Sortable-Placeholder',

      filter: '.Group--add',
      onMove: (evt) => !evt.related.classList.contains('Group--add'),

      onSort: () => this.onSortUpdate(),
    });
  }

  onSortUpdate() {
    const order = this.$('.Group:not(.Group--add)')
      .map(function () {
        return $(this).data('id');
      })
      .get();

    order.forEach((id, i) => {
      app.store.getById<Group>('groups', id)?.pushData({
        attributes: { position: i },
      });
    });

    app.request({
      url: app.forum.attribute('apiUrl') + '/groups/order',
      method: 'POST',
      body: { order },
    });
  }
}
