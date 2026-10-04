import Component, { type ComponentAttrs } from '../Component';
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
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    groups(): Group[];
    groupItem(group: Group): Mithril.Children;
    isSelected(group: Group): boolean;
    toggle(group: Group): void;
}
