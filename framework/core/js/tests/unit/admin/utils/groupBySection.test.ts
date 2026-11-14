import groupBySection from '../../../../src/admin/utils/groupBySection';

type Entry = { key: string; section?: string };

const group = (entries: Entry[], sections: string[]) =>
  groupBySection(entries, (e) => e.section, sections).map((g) => [g.section, g.entries.map((e) => e.key)]);

describe('groupBySection', () => {
  test('groups entries by their section, in section order', () => {
    expect(
      group(
        [
          { key: 'a', section: 'two' },
          { key: 'b', section: 'one' },
          { key: 'c', section: 'two' },
        ],
        ['one', 'two']
      )
    ).toEqual([
      ['one', ['b']],
      ['two', ['a', 'c']],
    ]);
  });

  test('an entry without a section joins the section of the entry before it', () => {
    expect(group([{ key: 'a', section: 'one' }, { key: 'added' }, { key: 'b', section: 'two' }], ['one', 'two'])).toEqual([
      ['one', ['a', 'added']],
      ['two', ['b']],
    ]);
  });

  test('entries before the first section form a leading group', () => {
    expect(group([{ key: 'notice' }, { key: 'a', section: 'one' }], ['one'])).toEqual([
      [null, ['notice']],
      ['one', ['a']],
    ]);
  });

  test('an unknown section counts as no section', () => {
    expect(
      group(
        [
          { key: 'a', section: 'one' },
          { key: 'b', section: 'missing' },
        ],
        ['one']
      )
    ).toEqual([['one', ['a', 'b']]]);
  });

  test('without sections everything is one leading group', () => {
    expect(group([{ key: 'a' }, { key: 'b' }], [])).toEqual([[null, ['a', 'b']]]);
  });
});
