import app from '../app';
import Component, { type ComponentAttrs } from '../Component';
import Icon from './Icon';
import MultiSelect from './MultiSelect';
import classList from '../utils/classList';
import withAttr from '../utils/withAttr';
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
  /** Shown until a group is chosen. */
  placeholder?: string;
  /** Describes the selector to assistive technology. */
  'aria-label'?: string;
}

/**
 * Picks one or more groups by name, as a standard select (or a MultiSelect
 * with `multiple`), so it looks like every other choice in a form.
 *
 * ```tsx
 * <GroupSelector value={this.groupId} onchange={(id) => (this.groupId = id)} exclude={[Group.GUEST_ID]} />
 * ```
 */
export default class GroupSelector<CustomAttrs extends IGroupSelectorAttrs = IGroupSelectorAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { className, multiple, value, onchange, id, placeholder, 'aria-label': ariaLabel } = this.attrs;
    const groups = this.groups();

    if (multiple) {
      return (
        <MultiSelect
          className={classList('GroupSelector', className)}
          id={id}
          aria-label={ariaLabel}
          options={Object.fromEntries(groups.map((group) => [group.id()!, group.namePlural()]))}
          value={Array.isArray(value) ? value : []}
          onchange={onchange}
        />
      );
    }

    const selected = groups.some((group) => group.id() === value) ? (value as string) : '';

    // Select's options are an object, whose integer-like keys (group ids) can't
    // hold the groups' own order or a placeholder ahead of them, so this is its
    // markup with the options as a list.
    return (
      <span className={classList('Select GroupSelector', className)}>
        <select className="Select-input FormControl" id={id} aria-label={ariaLabel} value={selected} onchange={withAttr('value', onchange)}>
          {!selected && (
            <option value="" disabled>
              {placeholder ?? app.translator.trans('core.lib.group_selector.placeholder', {}, true)}
            </option>
          )}
          {groups.map((group) => (
            <option value={group.id()}>{group.namePlural()}</option>
          ))}
        </select>
        <Icon name="fas fa-sort" className="Select-caret" />
      </span>
    );
  }

  groups(): Group[] {
    const exclude = this.attrs.exclude ?? [];

    return (this.attrs.groups ?? app.store.all<Group>('groups')).filter((group) => !exclude.includes(group.id()!));
  }
}
