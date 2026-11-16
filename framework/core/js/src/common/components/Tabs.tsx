import Component from '../Component';
import type { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import Icon from './Icon';
import classList from '../utils/classList';

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
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const { tabs, active, className } = this.attrs;
    const prefix = this.idPrefix();

    return (
      <div className={classList('Tabs', className)}>
        <div className="Tabs-nav" role="tablist" onkeydown={this.onkeydown.bind(this)}>
          {tabs.map((tab) => {
            const selected = tab.id === active;

            return (
              <button
                type="button"
                key={tab.id}
                id={`${prefix}-tab-${tab.id}`}
                className="Button Button--link Tabs-tab"
                role="tab"
                active={selected || undefined}
                aria-disabled={tab.disabled ? 'true' : undefined}
                aria-selected={selected ? 'true' : 'false'}
                aria-controls={`${prefix}-panel`}
                tabindex={selected ? 0 : -1}
                data-tab={tab.id}
                onclick={() => !tab.disabled && this.select(tab.id)}
              >
                {tab.icon ? <Icon name={tab.icon} className="Tabs-tabIcon" /> : null}
                <span className="Tabs-tabLabel">{tab.label}</span>
                {tab.badge != null && tab.badge !== false && tab.badge !== 0 ? <span className="Tabs-badge">{tab.badge}</span> : null}
                {tab.indicator ? (
                  tab.indicatorLabel ? (
                    <span className="Tabs-indicator" role="img" aria-label={tab.indicatorLabel} title={tab.indicatorLabel} />
                  ) : (
                    <span className="Tabs-indicator" aria-hidden="true" />
                  )
                ) : null}
              </button>
            );
          })}
        </div>
        <hr className="Tabs-divider" />
        <div className="Tabs-content" id={`${prefix}-panel`} role="tabpanel" aria-labelledby={`${prefix}-tab-${active}`} tabindex={0}>
          {vnode.children}
        </div>
      </div>
    );
  }

  idPrefix(): string {
    return this.attrs.idPrefix || 'Tabs';
  }

  select(id: string) {
    if (id !== this.attrs.active) this.attrs.onchange(id);
  }

  /**
   * Arrow keys, Home and End move between tabs, following the WAI-ARIA tabs pattern
   * with automatic activation.
   */
  onkeydown(e: KeyboardEvent) {
    const tabs = this.attrs.tabs.filter((tab) => !tab.disabled);
    const index = tabs.findIndex((tab) => tab.id === this.attrs.active);
    const rtl = document.documentElement.dir === 'rtl';
    let next: number | null = null;

    switch (e.key) {
      case 'ArrowRight':
        next = index + (rtl ? -1 : 1);
        break;
      case 'ArrowLeft':
        next = index + (rtl ? 1 : -1);
        break;
      case 'Home':
        next = 0;
        break;
      case 'End':
        next = tabs.length - 1;
        break;
      default:
        return;
    }

    e.preventDefault();

    const tab = tabs[(next + tabs.length) % tabs.length];

    this.select(tab.id);

    // Focus after the redraw so the newly active tab is the one in the tab order.
    requestAnimationFrame(() => document.getElementById(`${this.idPrefix()}-tab-${tab.id}`)?.focus());
  }
}
