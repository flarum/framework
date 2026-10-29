import app from '../../app';
import Dropdown from '../../../common/components/Dropdown';
import Separator from '../../../common/components/Separator';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import classList from '../../../common/utils/classList';
import extractText from '../../../common/utils/extractText';
import type { Filter } from '../../../common/resource/types';
import type Mithril from 'mithril';
import ResourceFilterBar, { type ResourceFilterBarAttrs } from './ResourceFilterBar';

/** Core's `@phone` breakpoint. */
export const PHONE_QUERY = '(max-width: 767.98px)';

/** A media query that always matches: the bar is always compact. */
export const ALWAYS_QUERY = 'all';

/**
 * A filter bar that collapses into one "Filters (n)" dropdown while `query` matches (default:
 * phone width; `ALWAYS_QUERY` for always), with a labelled section per filter, for core's phone
 * sheet. Otherwise it is the plain bar.
 */
export default class CompactFilterBar<M extends Model = Model> extends ResourceFilterBar<M> {
  protected media: MediaQueryList | null;
  protected onMediaChange = () => m.redraw();

  constructor(attrs: ResourceFilterBarAttrs<M>, query: string = PHONE_QUERY) {
    super(attrs);

    this.media = typeof window.matchMedia === 'function' ? window.matchMedia(query) : null;
    this.media?.addEventListener?.('change', this.onMediaChange);
  }

  destroy(): void {
    super.destroy();

    this.media?.removeEventListener?.('change', this.onMediaChange);
  }

  isCompact(): boolean {
    return !!this.media?.matches;
  }

  /** While compact, one "Filters" dropdown (`filters`, 80); else the plain bar's items. */
  items(): ItemList<Mithril.Children> {
    if (!this.isCompact()) return super.items();

    const items = new ItemList<Mithril.Children>();
    const filters = this.toolbarFilters();

    if (filters.length) items.add('filters', this.compactView(filters), 80);

    return items;
  }

  /**
   * The compact "Filters (n)" dropdown: one section per filter, a heading with its label over the
   * same items its dropdown lists (`menuItems`). Flat menu items, so core's phone sheet styling
   * applies to them.
   */
  compactView(filters: [string, Filter][]): Mithril.Children {
    const count = filters.filter(([key]) => this.list.getFilter(key) !== '').length;
    const items: Mithril.Children[] = [];

    filters.forEach(([key, filter], index) => {
      if (index) items.push(<Separator />);

      const heading = (
        <div className="ResourceTable-filterGroupLabel" onclick={(e: MouseEvent) => e.stopPropagation()}>
          {filter.label}
        </div>
      );

      items.push(
        Object.assign(heading, { itemClassName: classList('Dropdown-header', 'ResourceTable-filterGroup', `ResourceTable-filterGroup--${key}`) })
      );

      this.menuItems(key, filter).forEach((item) => items.push(this.inGroup(item, key)));
    });

    return (
      <Dropdown
        className="ResourceTable-filtersMenu"
        buttonClassName={classList('Button', count && 'is-active')}
        icon="fas fa-filter"
        label={app.translator.trans('core.admin.resource.table.filters_button', { count })}
        accessibleToggleLabel={extractText(app.translator.trans('core.admin.resource.table.filters_button', { count }))}
        helperText={null}
        onshow={() => filters.forEach(([key, filter]) => this.loadRelation(key, filter))}
      >
        {items}
      </Dropdown>
    );
  }

  /** Mark a menu item's `<li>` as belonging to one filter's section of the compact menu. */
  inGroup(item: Mithril.Children, key: string): Mithril.Children {
    if (item && typeof item === 'object' && 'tag' in item) {
      const vnode = item as Mithril.Vnode<{ itemClassName?: string }> & { itemClassName?: string };
      const groupClass = classList('ResourceTable-filterGroupItem', `ResourceTable-filterGroupItem--${key}`);

      // listItems reads `attrs.itemClassName` first, so join an existing one rather than shadow it.
      if (vnode.attrs?.itemClassName) vnode.attrs.itemClassName = classList(vnode.attrs.itemClassName, groupClass);
      else vnode.itemClassName = groupClass;
    }

    return item;
  }
}
