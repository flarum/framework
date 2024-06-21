import listItems from '../../../../src/common/helpers/listItems';
import m from 'mithril';

describe('listItems', () => {
  it('should return an array of Vnodes', () => {
    const items = [m('div', 'Item 1'), m('div', 'Item 2'), m('div', 'Item 3')];

    const result = listItems(items);

    expect(result).toHaveLength(3);
    expect(result[0].tag).toBe('li');
    expect(result[0].children[0].children[0].children).toBe('Item 1');
    expect(result[1].children[0].children[0].children).toBe('Item 2');
    expect(result[2].children[0].children[0].children).toBe('Item 3');
  });

  it('should return an array of Vnodes with custom tag', () => {
    const items = [m('div', 'Item 1'), m('div', 'Item 2'), m('div', 'Item 3')];

    const result = listItems(items, 'customTag');

    expect(result).toHaveLength(3);
    expect(result[0].tag).toBe('customTag');
    expect(result[0].children[0].children[0].children).toBe('Item 1');
    expect(result[1].children[0].children[0].children).toBe('Item 2');
    expect(result[2].children[0].children[0].children).toBe('Item 3');
  });

  it('should return an array of Vnodes with custom tag and attributes', () => {
    const items = [m('div', 'Item 1'), m('div', 'Item 2'), m('div', 'Item 3')];

    const result = listItems(items, 'ul', { id: 'list' });

    expect(result).toHaveLength(3);
    expect(result[0].tag).toBe('ul');
    // @ts-ignore
    expect(result[0].attrs.id).toBe('list');
    expect(result[0].children[0].children[0].children).toBe('Item 1');
    expect(result[1].children[0].children[0].children).toBe('Item 2');
    expect(result[2].children[0].children[0].children).toBe('Item 3');
  });
});
