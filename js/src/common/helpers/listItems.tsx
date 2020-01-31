import Separator from '../components/Separator';

export function isSeparator(item) {
    return item?.tag === Separator;
}

export function withoutUnnecessarySeparators(items) {
    const newItems = [];
    let prevItem;

    items.forEach((item, i) => {
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
 *
 * @param {*} items
 * @return {Array}
 */
export default function listItems(items) {
    if (!(items instanceof Array)) items = [items];

    return withoutUnnecessarySeparators(items).map(item => {
        const isListItem = item.tag?.isListItem;
        const active = item.tag?.isActive && item.tag.isActive(item.attrs);
        const className = item.attrs?.itemClassName || item.itemClassName;

        if (isListItem) {
            item.attrs = item.attrs || {};
            item.attrs.key = item.attrs.key || item.itemName;
            item.key = item.attrs.key;
        }

        const node = isListItem ? (
            item
        ) : (
            <li
                className={classNames(className, [item.itemName && `item-${item.itemName}`, active && 'active'])}
                key={item.attrs?.key || item.itemName}
            >
                {item}
            </li>
        );

        node.state = node.state || {};

        return node;
    });
}
