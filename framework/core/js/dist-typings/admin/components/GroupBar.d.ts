import Component, { ComponentAttrs } from '../../common/Component';
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
    groups: Group[];
    /** sortablejs, lazy-loaded; attached once available. */
    sortable: typeof import('sortablejs') | null;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    view(): JSX.Element;
    onGroupBarCreate(vnode: Mithril.VnodeDOM): void;
    /**
     * Attach sortable once both the element exists and sortablejs has loaded. Called
     * from both `oncreate` and the lazy import's resolution, so it runs whichever
     * completes last.
     */
    makeSortable(): void;
    onSortUpdate(): void;
}
