import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';
import Separator from '../components/Separator';
import classList from '../utils/classList';

export interface ModdedVnodeAttrs {
  itemClassName?: string;
  key?: string;
}

export type ModdedVnode<Attrs> = Mithril.Vnode<ModdedVnodeAttrs, Component<Attrs> | {}> & {
  itemName?: string;
  itemClassName?: string;
  tag: Mithril.Vnode['tag'] & {
    isListItem?: boolean;
    isActive?: (attrs: ComponentAttrs) => boolean;
  };
};

function isSeparator<Attrs>(item: ModdedVnode<Attrs>): boolean {
  return item.tag === Separator;
}

function withoutUnnecessarySeparators<Attrs>(items: ModdedVnode<Attrs>[]): ModdedVnode<Attrs>[] {
  const newItems: ModdedVnode<Attrs>[] = [];
  let prevItem: ModdedVnode<Attrs>;

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
export default function listItems<Attrs extends Record<string, unknown>>(
  rawItems: ModdedVnode<Attrs> | ModdedVnode<Attrs>[],
  customTag: string | Component<Attrs> = 'li',
  attributes: Attrs = {} as Attrs
): Mithril.Vnode[] {
  const items = rawItems instanceof Array ? rawItems : [rawItems];
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
