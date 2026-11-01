import app from '../../app';
import Component, { type ComponentAttrs } from '../../../common/Component';
import Button from '../../../common/components/Button';
import Pill from '../../../common/components/Pill';
import type Model from '../../../common/Model';
import ItemList from '../../../common/utils/ItemList';
import classList from '../../../common/utils/classList';
import extractText from '../../../common/utils/extractText';
import type Mithril from 'mithril';
import type ResourceFilterBar from './ResourceFilterBar';
import ordered from './ordered';

export interface FilterChipsAttrs<M extends Model = Model> extends ComponentAttrs {
  bar: ResourceFilterBar<M>;
}

/**
 * One chip per set filter of a `ResourceFilterBar` (per value of a multiple filter), and "Clear
 * all" while a filter that can be cleared is set.
 */
export default class FilterChips<
  M extends Model = Model,
  CustomAttrs extends FilterChipsAttrs<M> = FilterChipsAttrs<M>,
> extends Component<CustomAttrs> {
  view(): Mithril.Children {
    return (
      <div className="ResourceTable-chips" role="group" aria-label={app.translator.trans('core.admin.resource.table.filters_label', {}, true)}>
        <div className="Pill-list">{this.chipItems().toArray()}</div>
        {this.clearable() && (
          <Button className="Button Button--link ResourceTable-clearFilters" onclick={() => this.attrs.bar.clear()}>
            {app.translator.trans('core.admin.resource.table.clear_filters_button')}
          </Button>
        )}
      </div>
    );
  }

  /** Whether a set filter is not required, so "Clear all" has something to clear. */
  clearable(): boolean {
    const bar = this.attrs.bar;

    return ordered(bar.filters).some(([key, filter]) => !filter.required && bar.list.getFilter(key) !== '');
  }

  /**
   * "{label}: {value}" and a remove button per set filter; a multiple filter gets one chip per
   * value, a required one no remove button. A chip whose filter key a gambit in the search also
   * sends is marked overridden, since the gambit wins; one the search sends with the opposite sign
   * (`-suspended` vs `suspended`) is marked as conflicting.
   */
  chipItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const bar = this.attrs.bar;
    const searchKeys = bar.searchFilterKeys();

    ordered(bar.filters).forEach(([key, filter], index) => {
      const value = bar.list.getFilter(key);

      if (value === '') return;

      const relation = bar.searchRelation(key, value, searchKeys);
      const note =
        relation === 'overridden'
          ? app.translator.trans('core.admin.resource.table.overridden_by_search', {}, true)
          : relation === 'conflict'
            ? app.translator.trans('core.admin.resource.table.conflicts_with_search', {}, true)
            : null;
      const label = extractText(filter.label);

      const multiple = bar.isMultiple(filter);

      bar.values(filter, value).forEach((item, position) => {
        items.add(
          multiple ? `${key}:${item}` : key,
          <Pill
            className={classList(
              'ResourceTable-chip',
              `ResourceTable-chip--${key}`,
              filter.required && 'ResourceTable-chip--required',
              relation === 'overridden' && 'ResourceTable-chip--overridden',
              relation === 'conflict' && 'ResourceTable-chip--conflict'
            )}
            deletable={!filter.required}
            deleteLabel={app.translator.trans('core.admin.resource.table.remove_filter_label', { label }, true)}
            ondelete={() => bar.list.setFilter(key, bar.without(filter, value, item))}
          >
            <span className="ResourceTable-chipText" title={note ?? undefined}>
              {app.translator.trans('core.admin.resource.table.filter_label', {
                label,
                value: multiple ? bar.itemLabel(key, filter, item) : bar.valueLabel(key, filter, item),
              })}
            </span>
            {note && <span className="visually-hidden">({note})</span>}
          </Pill>,
          100 - index - position / 100
        );
      });
    });

    return items;
  }
}
