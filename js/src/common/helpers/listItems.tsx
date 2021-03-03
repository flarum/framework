import * as Mithril from 'mithril';
import Separator from '../components/Separator';
import classList from '../utils/classList';

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
 * The `listItems` helper wraps a collection of components in <li> tags,
 * stripping out any unnecessary `Separator` components.
 */
export default function listItems(items: Mithril.Vnode | Array<Mithril.Vnode>): Array<Mithril.Vnode> {
  if (!(items instanceof Array)) items = [items];

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
      <li
        className={classList([className, item.itemName && `item-${item.itemName}`, active && 'active'])}
        key={(item.attrs && item.attrs.key) || item.itemName}
      >
        {item}
      </li>
    );

    return node;
  });
}
