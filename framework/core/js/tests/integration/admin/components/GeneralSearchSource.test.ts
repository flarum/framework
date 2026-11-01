import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import GeneralSearchSource from '../../../../src/admin/components/GeneralSearchSource';
import { app } from '../../../../src/admin';

beforeAll(() =>
  bootstrapAdmin({
    extensions: {
      'acme-search': {
        id: 'acme-search',
        version: '1.0.0',
        description: '',
        icon: { name: 'fas fa-cog' },
        links: {},
        extra: { 'flarum-extension': { title: 'Acme Search' } },
      },
    },
    settings: {
      extensions_enabled: JSON.stringify(['acme-search']),
    },
  })
);

describe('GeneralSearchSource', () => {
  beforeAll(() => {
    app.boot();
  });

  describe('search results with tree property', () => {
    beforeEach(() => {
      app.generalIndex
        .group('test-extension', { label: 'Test Extension', icon: { name: 'fas fa-cog' }, link: '/admin/test' })
        .for('test-extension')
        .add('settings', [
          {
            id: 'test_setting',
            label: 'Max file size',
            tree: ['Preferences'],
          },
        ]);
    });

    it('produces an array tree when setting has a tree property', async () => {
      const source = new GeneralSearchSource();
      await source.search('max file', 10);

      const results = source['results'].get('max file') ?? [];
      const result = results.find((r) => r.id.includes('test_setting'));

      expect(result).toBeDefined();
      expect(Array.isArray(result!.tree)).toBe(true);
      expect(result!.tree).toContain('Max file size');
      expect(result!.tree).toContain('Preferences');
    });

    it('does not crash when calling .map() on the tree', async () => {
      const source = new GeneralSearchSource();
      await source.search('max file', 10);

      const results = source['results'].get('max file') ?? [];
      const result = results.find((r) => r.id.includes('test_setting'));

      expect(result).toBeDefined();
      expect(() => result!.tree.map((part) => part.toUpperCase())).not.toThrow();
    });

    it('places tree entries before the label', async () => {
      const source = new GeneralSearchSource();
      await source.search('max file', 10);

      const results = source['results'].get('max file') ?? [];
      const result = results.find((r) => r.id.includes('test_setting'));

      expect(result).toBeDefined();
      expect(result!.tree[0]).toBe('Preferences');
      expect(result!.tree[result!.tree.length - 1]).toBe('Max file size');
    });
  });

  describe('search results without tree property', () => {
    beforeEach(() => {
      app.generalIndex
        .group('test-extension-no-tree', { label: 'Test Extension 2', icon: { name: 'fas fa-cog' }, link: '/admin/test2' })
        .for('test-extension-no-tree')
        .add('settings', [
          {
            id: 'another_setting',
            label: 'Upload limit',
          },
        ]);
    });

    it('produces a single-element tree with just the label', async () => {
      const source = new GeneralSearchSource();
      await source.search('upload limit', 10);

      const results = source['results'].get('upload limit') ?? [];
      const result = results.find((r) => r.id.includes('another_setting'));

      expect(result).toBeDefined();
      expect(Array.isArray(result!.tree)).toBe(true);
      expect(result!.tree).toEqual(['Upload limit']);
    });
  });

  describe('extension registered settings', () => {
    beforeAll(() => {
      app.registry
        .for('acme-search')
        .registerSetting({ setting: 'acme-search.first_limit', type: 'text', label: 'First quota limit' })
        .registerSetting({ setting: 'acme-search.second_limit', type: 'text', label: 'Second quota limit' });
    });

    it('builds each result id from the setting key', async () => {
      const source = new GeneralSearchSource();
      await source.search('quota limit', 10);

      const ids = (source['results'].get('quota limit') ?? []).map((r) => r.id);

      expect(ids).toEqual(expect.arrayContaining(['acme-search-acme-search.first_limit', 'acme-search-acme-search.second_limit']));
      expect(new Set(ids).size).toBe(ids.length);
    });
  });
});
