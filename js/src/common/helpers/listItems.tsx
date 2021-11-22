import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';
import Separator from '../components/Separator';
import classList from '../utils/classList';

type ModdedVnodeAttrs = {
  itemClassName?: string;
  key?: string;
};

type ModdedTag = Mithril.Vnode['tag'] & {
  isListItem?: boolean;
  isActive?: (attrs: ComponentAttrs) => boolean;
};

type ModdedVnode = Mithril.Vnode<ModdedVnodeAttrs> & { itemName?: string; itemClassName?: string; tag: ModdedTag };

type ModdedChild = ModdedVnode | string | number | boolean | null | undefined;
interface ModdedChildArray extends Array<ModdedChildren> {}
type ModdedChildren = ModdedChild | ModdedChildArray;
type ModdedChildrenWithItemname = ModdedChildren & { itemName?: string };

function isVnode(item: ModdedChildren): item is Mithril.Vnode {
  return typeof item === 'object' && item !== null && 'tag' in item;
}

function isSeparator(item: ModdedChildren): boolean {
  return isVnode(item) && item.tag === Separator;
}

function withoutUnnecessarySeparators(items: ModdedChildrenWithItemname[]): ModdedChildrenWithItemname[] {
  const newItems: ModdedChildrenWithItemname[] = [];
  let prevItem: ModdedChildren;

  items.filter(Boolean).forEach((item, i: number) => {
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
export default function listItems<Attrs extends ComponentAttrs>(
  rawItems: ModdedChildrenWithItemname[],
  customTag: string | (new () => Component<Attrs>) = 'li',
  attributes: Attrs = {} as Attrs
): Mithril.Vnode[] {
  const items = rawItems instanceof Array ? rawItems : [rawItems];
  const Tag = customTag;

  return withoutUnnecessarySeparators(items).map((item) => {
    if (!isVnode(item)) {
      return <Tag className={item.itemName && `item-${item.itemName}`}>{item}</Tag>;
    }

    if (item.tag.isListItem) {
      item.attrs = item.attrs || {};
      item.attrs.key = item.attrs.key || item.itemName;
      item.key = item.attrs.key;

      return item;
    }

    const active = item.tag.isActive?.(item.attrs);
    const className = item.attrs?.itemClassName || item.itemClassName;

    return (
      <Tag className={classList([className, active && 'active'])} key={item?.attrs?.key || item.itemName} {...attributes}>
        {item}
      </Tag>
    );
  });
}
