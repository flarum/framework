import app from '../../app';
import GroupBadge from '../../../common/components/GroupBadge';
import type Group from '../../../common/models/Group';
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

  /** Delete on the selection too. */
  resourceActions(): ItemList<ResourceAction<Group>> {
    const items = super.resourceActions();

    if (items.has('delete')) items.setContent('delete', { ...items.get('delete'), scope: 'both' });

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
