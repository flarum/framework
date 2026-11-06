import type ItemList from '../../../common/utils/ItemList';

/** Item-list entries in render order: priority descending, insertion order for ties. */
export default function ordered<T>(list: ItemList<T>): [string, T][] {
  return Object.entries(list.toObject())
    .map(([key, item], index) => ({ key, content: item.content as T, priority: item.priority, index }))
    .sort((a, b) => b.priority - a.priority || a.index - b.index)
    .map(({ key, content }) => [key, content]);
}
