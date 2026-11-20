import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import mq from 'mithril-query';
import GeneralSearchSource, { GeneralSearchResult } from '../../../../src/admin/components/GeneralSearchSource';
import SearchManager from '../../../../src/common/SearchManager';
import app from '../../../../src/admin/app';

beforeAll(() => bootstrapAdmin());

describe('GeneralSearchSource highlighting', () => {
  const render = (query: string, tree: string[], help?: string) => {
    const source = new GeneralSearchSource();
    (source as any).results.set(query, [new GeneralSearchResult('demo', 'Demo', { name: 'fas fa-cog' }, tree, '/demo', help)]);

    return mq({ view: () => m('ul', source.view(query)) });
  };

  const marks = (rendered: any) => rendered.find('mark').map((mark: Element) => mark.textContent);

  test('labels and help are rendered as text, not HTML', () => {
    const rendered = render('format', ['Mention format for <username>'], 'Write <b>@alice</b> here. Tags &amp; mentions in the format.');

    expect(rendered).not.toHaveElement('username');
    expect(rendered).not.toHaveElement('.GeneralSearchResult-help b');
    expect(rendered.find('.GeneralSearchResult-tree')[0].textContent).toContain('Mention format for <username>');
    expect(rendered.find('.GeneralSearchResult-help')[0].textContent).toContain('<b>@alice</b> here. Tags &amp; mentions');
  });

  test('matches are marked', () => {
    expect(marks(render('format', ['Mention format'], 'The format')).length).toBe(2);
  });

  test('a search does not match inside escaped characters', () => {
    const rendered = render('amp', ['Tom & Jerry amp']);

    expect(marks(rendered)).toEqual(['amp']);
    expect(rendered.find('.GeneralSearchResult-tree')[0].textContent).toContain('Tom & Jerry amp');
  });
});

describe('GeneralSearchSource minimum search length', () => {
  it('does not follow a raised forum minimum', () => {
    app.forum = app.store.createRecord('forums', { attributes: { searchMinLength: 6 } });

    expect(new GeneralSearchSource().minSearchLength()).toBe(SearchManager.MIN_SEARCH_LEN);
  });

  it('searches from one character in CJK mode', () => {
    app.forum = app.store.createRecord('forums', { attributes: { searchMinLength: 3, searchCjkMode: true } });

    expect(new GeneralSearchSource().minSearchLength()).toBe(1);
  });

  it('follows a lowered forum minimum', () => {
    app.forum = app.store.createRecord('forums', { attributes: { searchMinLength: 1 } });

    expect(new GeneralSearchSource().minSearchLength()).toBe(1);
  });
});
