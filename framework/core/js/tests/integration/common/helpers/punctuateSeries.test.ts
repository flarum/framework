import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { app } from '../../../../src/forum';
import punctuateSeries from '../../../../src/common/helpers/punctuateSeries';
import extractText from '../../../../src/common/utils/extractText';
import m from 'mithril';

beforeAll(() => bootstrapForum());

describe('punctuateSeries', () => {
  beforeAll(() => {
    app.boot();
    app.translator.addTranslations({
      'core.lib.series.glue_text': ', ',
      'core.lib.series.two_text': '{first} and {second}',
      'core.lib.series.three_text': '{first}, {second}, and {third}',
    });
  });

  test('returns zero or one item as is', () => {
    expect(punctuateSeries([])).toEqual([]);
    expect(punctuateSeries(['Toby'])).toEqual(['Toby']);
  });

  test('joins two items', () => {
    expect(extractText(punctuateSeries(['Toby', 'Franz']))).toBe('Toby and Franz');
  });

  test('joins three items', () => {
    expect(extractText(punctuateSeries(['Toby', 'Franz', 'Dominion']))).toBe('Toby, Franz, and Dominion');
  });

  test('glues every middle item without a trailing separator', () => {
    expect(extractText(punctuateSeries(['Toby', 'Franz', 'Dominion', 'Ian']))).toBe('Toby, Franz, Dominion, and Ian');
  });

  test('keeps vnode items', () => {
    const items = ['a', 'b', 'c', 'd'].map((name) => m('a', name));
    const vnodes = punctuateSeries(items) as any[];

    expect(extractText(vnodes)).toBe('a, b, c, and d');
    items.forEach((item) => expect(JSON.stringify(vnodes)).toContain(JSON.stringify(item)));
  });
});
