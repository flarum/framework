import app from '../app';
import Component, { type ComponentAttrs } from '../Component';
import GroupBadge from './GroupBadge';
import classList from '../utils/classList';
import type Group from '../models/Group';
import type Mithril from 'mithril';

export interface IGroupSelectorAttrs extends ComponentAttrs {
  /** The selected group id, or ids with `multiple`. */
  value: string | string[];
  onchange: (value: any) => void;
  /** Allow selecting several groups; `value` and `onchange` then use arrays of ids. */
  multiple?: boolean;
  /** The groups to offer. Defaults to every group in the store, i.e. those the actor can see. */
  groups?: Group[];
  /** Ids of groups to leave out, e.g. `[Group.GUEST_ID]`. */
  exclude?: string[];
  /** Describes the selector to assistive technology. */
  'aria-label'?: string;
}

/**
 * Picks one or more groups, shown the way the admin's group bar shows them:
 * each group as a tile with its badge and plural name.
 *
 * ```tsx
 * <GroupSelector value={this.groupId} onchange={(id) => (this.groupId = id)} exclude={[Group.GUEST_ID]} />
 * ```
 */
export default class GroupSelector<CustomAttrs extends IGroupSelectorAttrs = IGroupSelectorAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { className, multiple, 'aria-label': ariaLabel, id } = this.attrs;

    return (
      <div className={classList('GroupSelector', className)} id={id} role={multiple ? 'group' : 'radiogroup'} aria-label={ariaLabel}>
        {this.groups().map((group) => this.groupItem(group))}
      </div>
    );
  }

  groups(): Group[] {
    const exclude = this.attrs.exclude ?? [];

    return (this.attrs.groups ?? app.store.all<Group>('groups')).filter((group) => !exclude.includes(group.id()!));
  }

  groupItem(group: Group): Mithril.Children {
    const selected = this.isSelected(group);

    return (
      <button
        type="button"
        key={group.id()}
        role={this.attrs.multiple ? 'checkbox' : 'radio'}
        aria-checked={selected ? 'true' : 'false'}
        className={classList('Button GroupSelector-group', { active: selected })}
        onclick={() => this.toggle(group)}
      >
        <GroupBadge group={group} label={null} className="GroupSelector-badge" />
        <span className="GroupSelector-name">{group.namePlural()}</span>
      </button>
    );
  }

  isSelected(group: Group): boolean {
    const value = this.attrs.value;

    return Array.isArray(value) ? value.includes(group.id()!) : value === group.id();
  }

  toggle(group: Group): void {
    const id = group.id()!;

    if (!this.attrs.multiple) {
      this.attrs.onchange(id);
      return;
    }

    const value = Array.isArray(this.attrs.value) ? this.attrs.value : [];

    this.attrs.onchange(value.includes(id) ? value.filter((other) => other !== id) : [...value, id]);
  }
}
