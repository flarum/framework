import Component from '../Component';
import type { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
export interface TabItem {
    id: string;
    label: Mithril.Children;
    icon?: string;
    /** Short count or label shown after the tab label, e.g. a number of pending items. */
    badge?: Mithril.Children;
    /** Shows a dot after the label, e.g. to flag unsaved changes on that tab. */
    indicator?: boolean;
    /** Accessible text for the indicator dot. */
    indicatorLabel?: string;
    disabled?: boolean;
}
export interface ITabsAttrs extends ComponentAttrs {
    tabs: TabItem[];
    active: string;
    onchange: (id: string) => void;
    /** Prefix for the tab and panel element ids; must be unique on the page. */
    idPrefix?: string;
}
/**
 * An accessible tab bar with a single panel for the active tab's content.
 *
 * The component is controlled: the caller owns which tab is active and
 * renders that tab's content as the children.
 *
 * @example
 * <Tabs tabs={[{ id: 'general', label: 'General' }, { id: 'advanced', label: 'Advanced' }]}
 *       active={this.tab} onchange={(tab) => (this.tab = tab)}>
 *   {this.tab === 'general' ? <GeneralSettings /> : <AdvancedSettings />}
 * </Tabs>
 */
export default class Tabs<CustomAttrs extends ITabsAttrs = ITabsAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    idPrefix(): string;
    select(id: string): void;
    /**
     * Arrow keys, Home and End move between tabs, following the WAI-ARIA tabs pattern
     * with automatic activation.
     */
    onkeydown(e: KeyboardEvent): void;
}
