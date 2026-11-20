import app from '../../app';
import GroupBadge from '../../../common/components/GroupBadge';
import IconImageInput from '../../../common/components/IconImageInput';
import IconImageState from '../../../common/states/IconImageState';
import type Group from '../../../common/models/Group';
import type { ResourceAction } from '../../../common/resource/actions';
import ResourceDefinition from '../../../common/resource/ResourceDefinition';
import { patchInput } from '../../../common/resource/field';
import type { Field } from '../../../common/resource/types';
import type ItemList from '../../../common/utils/ItemList';
import extractText from '../../../common/utils/extractText';

/**
 * The groups definition (`app.resources.get('groups')`): a badge column in front of the names, help
 * for the icon, an icon image after it, and a group named by its singular name. The colour and icon
 * inputs come from the descriptor's `ui.widget`.
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

    if (fields.has('icon')) {
      fields.add('iconImage', this.iconImageField() as Field<Group>, fields.getPriority('icon') - 1);
    }

    return fields;
  }

  /**
   * An image shown instead of the icon. It has its own endpoint, so it is uploaded (or removed)
   * once the group is saved, a new group once it has an id, as in core's group modal. Not offered
   * on a group the actor may not edit (`canEdit`), as its endpoint asks for that ability.
   */
  iconImageField(): Field<Group, IconImageState> {
    return {
      label: app.translator.trans('core.admin.groups.fields.icon_image'),
      value: (group) => new IconImageState(group ?? app.store.createRecord<Group>(this.type)),
      serialize: () => ({}),
      isChanged: (state) => state.isDirty(),
      afterSave: (group, state, { errorHandler }) => state.save(group, { errorHandler }),
      input: ({ stream }) => (
        <div onremove={() => stream().dispose()}>
          <div className="helpText">{app.translator.trans('core.admin.groups.icon_image_text')}</div>
          <IconImageInput state={stream()} />
        </div>
      ),
      when: (group) => !group?.exists || group.attribute<boolean>('canEdit') !== false,
    };
  }

  /**
   * Delete on the selection too. Which groups it applies to is the server's `canDelete`: the
   * policy never lets anyone delete the admin, guest and member groups.
   */
  resourceActions(): ItemList<ResourceAction<Group>> {
    const items = super.resourceActions();

    if (items.has('delete')) {
      items.setContent('delete', { ...items.get('delete'), scope: 'both' });
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
