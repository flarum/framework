import app from '../../app';
import GroupBadge from '../../../common/components/GroupBadge';
import Group from '../../../common/models/Group';
import type { ResourceAction } from '../../../common/resource/actions';
import ResourceDefinition from '../../../common/resource/ResourceDefinition';
import { patchInput } from '../../../common/resource/field';
import type { Field } from '../../../common/resource/types';
import type ItemList from '../../../common/utils/ItemList';
import extractText from '../../../common/utils/extractText';

/**
 * The groups definition (`app.resources.get('groups')`): a badge column in front of the names, help
 * for the icon, and a group named by its singular name. The colour and icon inputs come from the
 * descriptor's `ui.widget`.
 */
export default class GroupsDefinition extends ResourceDefinition<Group> {
  constructor(type: string = 'groups') {
    super(type);
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

    patchInput(fields, 'icon', { help: app.translator.trans('core.admin.groups.icon_text', { a: <a href={app.refs.fontawesome} tabindex="-1" /> }) });

    return fields;
  }

  /**
   * Delete on the selection too, never for the Admin group: deleting it would leave the forum
   * without administrators, so it is not offered, as in core's group modal.
   */
  resourceActions(): ItemList<ResourceAction<Group>> {
    const items = super.resourceActions();

    if (items.has('delete')) {
      const action = items.get('delete');

      items.setContent('delete', {
        ...action,
        scope: 'both',
        applies: (group: Group) => group.id() !== Group.ADMINISTRATOR_ID && (!action.applies || action.applies(group)),
      });
    }

    return items;
  }

  recordLabel(group: Group): string {
    return group.nameSingular() || super.recordLabel(group);
  }

  /** A handful of records, nothing to export. */
  canExport(): boolean {
    return false;
  }

  label(count: number): string {
    return extractText(app.translator.trans('core.admin.groups.label', { count }));
  }
}
