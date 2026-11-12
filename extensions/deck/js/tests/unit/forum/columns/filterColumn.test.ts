import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';
import deckColumnTypes, { registerDefaultColumnTypes } from '../../../../src/forum/columns/deckColumnTypes';
import type { DeckColumnField } from '../../../../src/forum/columns/DeckColumnType';
import { boot, loadDeckTranslations } from '../helpers';

beforeAll(() => {
  boot();
  loadDeckTranslations();
  if (!deckColumnTypes.has('filter')) registerDefaultColumnTypes();
});

const queryField = (): DeckColumnField => deckColumnTypes.get('filter').fields!().find((field) => field.key === 'query')!;

const invalidText = (value: string): string => {
  const text = queryField().invalidText;

  return extractText(typeof text === 'function' ? text(value) : text);
};

describe('the custom filter field', () => {
  it('takes several filters, negated ones too', () => {
    expect(queryField().parse!('is:unread -author:normal')).toBe('is:unread -author:normal');
  });

  // Discussions filter by their authors' groups, so a column can combine the two.
  it('takes a filter on the groups of discussion authors', () => {
    expect(queryField().parse!('group:mods is:unread')).toBe('group:mods is:unread');
  });

  // A pasted query can hold tabs or runs of spaces between its filters.
  it('takes filters separated by any whitespace', () => {
    expect(queryField().parse!('is:unread \t  -author:normal')).not.toBeNull();
  });

  it('refuses plain words and filters that aren’t for discussions', () => {
    expect(queryField().parse!('email:admin is:unread')).toBeNull();
    expect(queryField().parse!('hello is:unread')).toBeNull();
  });

  // email: is a filter for members, so "plain words aren't supported" left
  // people looking for words they hadn't typed.
  it('names what it couldn’t use', () => {
    expect(invalidText('email:admin is:unread -author:normal')).toMatch(/^email:admin isn't a filter for discussions\./);
  });

  // That sentence is what sent people looking for words they hadn't typed.
  it('doesn’t blame plain words when it names the parts', () => {
    expect(invalidText('email:admin is:unread')).not.toContain('Plain words');
  });

  it('names every part it couldn’t use', () => {
    expect(invalidText('email:admin is:unread hello')).toMatch(/^email:admin hello aren't filters for discussions\./);
  });

  it('asks for filters when nothing was typed', () => {
    expect(invalidText('')).toBe(extractText(app.translator.trans('flarum-deck.forum.add_column.filter_invalid_message')));
  });
});
