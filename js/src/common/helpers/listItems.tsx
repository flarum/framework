import type Mithril from 'mithril';
import Separator from '../components/Separator';
import classList from '../utils/classList';
import type Component from '../Component';

function isSeparator(item): boolean {
  return item.tag === Separator;
}

function withoutUnnecessarySeparators(items: Array<Mithril.Vnode>): Array<Mithril.Vnode> {
  const newItems = [];
  let prevItem;

  items.filter(Boolean).forEach((item: Mithril.Vnode, i: number) => {
    if (!isSeparator(item) || (prevItem && !isSeparator(prevItem) && i !== items.length - 1)) {
      prevItem = item;
      newItems.push(item);
    }
  });

  return newItems;
}

/**
 * The `listItems` helper wraps an array of components in the provided tag,
 * stripping out any unnecessary `Separator` components.
 *
 * By default, this tag is an `<li>` tag, but this is customisable through the
 * second function parameter, `customTag`.
 */
export default function listItems<Attrs = Record<string, unknown>>(
  items: Mithril.Vnode | Mithril.Vnode[],
  customTag: string | Component<Attrs> = 'li',
  attributes: Attrs = {}
): Mithril.Vnode[] {
  if (!(items instanceof Array)) items = [items];

  const Tag = customTag;

  return withoutUnnecessarySeparators(items).map((item: Mithril.Vnode) => {
    const isListItem = item.tag && item.tag.isListItem;
    const active = item.tag && item.tag.isActive && item.tag.isActive(item.attrs);
    const className = (item.attrs && item.attrs.itemClassName) || item.itemClassName;

    if (isListItem) {
      item.attrs = item.attrs || {};
      item.attrs.key = item.attrs.key || item.itemName;
      item.key = item.attrs.key;
    }

    const node: Mithril.Vnode = isListItem ? (
      item
    ) : (
      <Tag
        className={classList([className, item.itemName && `item-${item.itemName}`, active && 'active'])}
        key={item?.attrs?.key || item.itemName}
        {...attributes}
      >
        {item}
      </Tag>
    );

    return node;
  });
}
