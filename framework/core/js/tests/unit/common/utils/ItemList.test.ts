import ItemList from '../../../../src/common/utils/ItemList';

describe('ItemList.toArray', () => {
  it('attaches itemName to object content', () => {
    const items = new ItemList<any>();
    items.add('greeting', { text: 'hello' }, 100);

    const [content] = items.toArray();

    expect(content.text).toBe('hello');
    expect(content.itemName).toBe('greeting');
  });

  it('boxes primitive content so itemName is readable', () => {
    const items = new ItemList<any>();
    items.add('greeting', 'hello', 100);

    const [content] = items.toArray();

    expect(typeof content).toBe('object');
    expect(content.itemName).toBe('greeting');
    expect(content.length).toBe(5);
    expect(content[0]).toBe('h');
  });

  it('keeps primitive content unboxed when asked', () => {
    const items = new ItemList<any>();
    items.add('greeting', 'hello', 100);

    expect(items.toArray(true)).toEqual(['hello']);
  });

  it('leaves null content as null', () => {
    const items = new ItemList<any>();
    items.add('sidebar', null, 100);

    expect(items.toArray()).toEqual([null]);
  });

  it('leaves undefined content as undefined', () => {
    const items = new ItemList<any>();
    items.add('sidebar', undefined, 100);

    expect(items.toArray()).toEqual([undefined]);
  });

  // A falsy check here would box these into objects and break `itemName` on them.
  it.each([
    ['zero', 0],
    ['false', false],
    ['empty string', ''],
  ])('still boxes %s content', (_label, value) => {
    const items = new ItemList<any>();
    items.add('falsy', value, 100);

    const [content] = items.toArray();

    expect(content).not.toBeNull();
    expect(content).not.toBeUndefined();
    expect(typeof content).toBe('object');
    expect(content.itemName).toBe('falsy');
    expect(items.toArray(true)).toEqual([value]);
  });

  it('orders items by priority with null content in the list', () => {
    const items = new ItemList<any>();
    items.add('first', 'a', 100);
    items.add('second', null, 50);
    items.add('third', 'b', 10);

    const contents = items.toArray(true);

    expect(contents).toEqual(['a', null, 'b']);
  });
});
