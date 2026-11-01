import app from '../../app';
import Group from '../../../common/models/Group';
import type Model from '../../../common/Model';
import type User from '../../../common/models/User';
import { patchFilter } from '../../../common/resource/fieldsFromDescriptor';
import ResourceDefinition from '../../../common/resource/ResourceDefinition';
import type { FieldDescriptor, FilterDescriptor } from '../../../common/resource/types';
import extractText from '../../../common/utils/extractText';

/**
 * The users definition (`app.resources.get('users')`). Its built-in `delete` stays on the rows (the
 * page's actions menu), so the list has no checkbox column until an extension adds a bulk-scoped
 * `ResourceAction` (or sets the `delete` action's `scope` to `'both'`). Row-scoped actions an
 * extension adds show in the page's actions menu. The group filter labels groups like the `groups` field does, and leaves out the guest group,
 * which no user is a member of.
 */
export default class UsersDefinition extends ResourceDefinition<User> {
  constructor(type: string = 'users') {
    super(type);
  }

  label(count: number): string {
    return extractText(app.translator.trans('core.admin.users.label', { count }));
  }

  /**
   * Administrators only: the export holds email addresses.
   */
  canExport(): boolean {
    return !!app.session.user?.isAdmin() && super.canExport();
  }

  filterInput(filter: FilterDescriptor, d: FieldDescriptor | null) {
    // Named apart from the `groups` relationship, so it takes that field's display hint.
    if (filter.key === 'group') d = this.descriptor?.fields.find((field) => field.name === 'groups') ?? d;

    return super.filterInput(filter, d);
  }

  filters() {
    const filters = super.filters();

    patchFilter(filters, 'group', { exclude: (model: Model) => model.id() === Group.GUEST_ID });

    return filters;
  }
}
