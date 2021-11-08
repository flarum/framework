import type Mithril from 'mithril';
import Separator from '../components/Separator';
import classList from '../utils/classList';
import type * as Component from '../Component';

function isSeparator(item: Mithril.Children): boolean {
  return item.tag === Separator;
}

function withoutUnnecessarySeparators(items: Mithril.Children): Mithril.Children {
  const newItems: Mithril.Children = [];
  let prevItem: Mithril.Child;

  items.filter(Boolean).forEach((item: Mithril.Vnode, i: number) => {
    if (!isSeparator(item) || (prevItem && !isSeparator(prevItem) && i !== items.length - 1)) {
      prevItem = item;
      newItems.push(item);
    }
  });

  return newItems;
}

export interface ModdedVnodeAttrs {
  itemClassName?: string;
  key?: string;
}

export type ModdedVnode<Attrs> = Mithril.Vnode<ModdedVnodeAttrs, Component.default<Attrs> | {}> & {
  itemName?: string;
  itemClassName?: string;
};

/**
 * The `listItems` helper wraps an array of components in the provided tag,
 * stripping out any unnecessary `Separator` components.
 *
 * By default, this tag is an `<li>` tag, but this is customisable through the
 * second function parameter, `customTag`.
 */
export default function listItems<Attrs extends Record<string, unknown>>(
  items: ModdedVnode<Attrs> | ModdedVnode<Attrs>[],
  customTag: string | Component.default<Attrs> = 'li',
  attributes: Attrs = {}
): Mithril.Vnode[] {
  if (!(items instanceof Array)) items = [items];

  const Tag = customTag;

  return withoutUnnecessarySeparators(items).map((item: ModdedVnode<Attrs>) => {
    const isListItem = item.tag?.isListItem;
    const active = item.tag?.isActive?.(item.attrs);
    const className = item.attrs?.itemClassName || item.itemClassName;

    if (isListItem) {
      item.attrs = item.attrs || {};
      item.attrs.key = item.attrs.key || item.itemName;
      item.key = item.attrs.key;
    }

    const node: Mithril.Vnode = isListItem ? (
      item
    ) : (
      // @ts-expect-error `Component` does not have any construct or call signatures
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
