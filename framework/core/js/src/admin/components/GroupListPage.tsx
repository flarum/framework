import type Mithril from 'mithril';

import app from '../../admin/app';

import GroupBadge from '../../common/components/GroupBadge';
import type { IPageAttrs } from '../../common/components/Page';
import type Group from '../../common/models/Group';
import ResourceDefinition from '../../common/resource/ResourceDefinition';
import { patchField } from '../../common/resource/fieldsFromDescriptor';
import type { Field } from '../../common/resource/types';
import type ItemList from '../../common/utils/ItemList';
import extractText from '../../common/utils/extractText';
import AdminPage from './AdminPage';
import ResourceTable from './resource/ResourceTable';

/**
 * The groups resource: a badge column in front of the names, help for the icon, and a group named
 * by its singular name. The colour and icon inputs come from the descriptor's `ui.widget`.
 */
export class GroupDefinition extends ResourceDefinition<Group> {
  constructor() {
    super('groups');
  }

  fields(): ItemList<Field<Group>> {
    const fields = super.fields();

    fields.add(
      'badge',
      {
        label: null,
        column: { priority: 100, render: (group: Group) => <GroupBadge group={group} label={null} /> },
        input: false,
      },
      100
    );

    if (fields.has('icon')) {
      const icon = fields.get('icon');

      if (icon.input && typeof icon.input === 'object') {
        patchField(fields, 'icon', {
          input: { ...icon.input, help: app.translator.trans('core.admin.groups.icon_text', { a: <a href={app.refs.fontawesome} tabindex="-1" /> }) },
        });
      }
    }

    return fields;
  }

  recordLabel(group: Group): string {
    return group.nameSingular() || super.recordLabel(group);
  }

  /** A handful of records, nothing to export. */
  canExport(): boolean {
    return false;
  }

  createTitle(): Mithril.Children {
    return app.translator.trans('core.admin.groups.create_title');
  }

  label(_n: number): string {
    return extractText(app.translator.trans('core.admin.groups.title'));
  }
}

/**
 * Admin page listing every group, with create, edit and (for all but the admin, guest and member
 * groups) delete. Reordering stays on the permissions page.
 */
export default class GroupListPage extends AdminPage {
  protected definition!: GroupDefinition;

  oninit(vnode: Mithril.Vnode<IPageAttrs, this>) {
    super.oninit(vnode);

    this.definition = new GroupDefinition();
  }

  headerInfo() {
    return {
      className: 'GroupListPage',
      icon: 'fas fa-user-tag',
      title: app.translator.trans('core.admin.groups.title'),
      description: app.translator.trans('core.admin.groups.description'),
    };
  }

  content() {
    return <ResourceTable definition={this.definition} className="GroupListPage-table" />;
  }
}
