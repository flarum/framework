import { jest } from '@jest/globals';
import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import GeneralSearchSource from '../../../../src/admin/components/GeneralSearchSource';
import { app } from '../../../../src/admin';

beforeAll(() => bootstrapAdmin());

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

  describe('settings result links', () => {
    beforeAll(() => {
      app.generalIndex
        .group('settings-links', { label: 'Settings Links', icon: { name: 'fas fa-cog' }, link: app.route('mail') })
        .for('settings-links')
        .add('settings', [
          {
            id: 'own_link_setting',
            label: 'Alpha setting',
            link: app.route('advanced'),
          },
          {
            id: 'fallback_setting',
            label: 'Fallback setting',
          },
          {
            id: 'empty_link_setting',
            label: 'Empty link setting',
            link: '',
          },
        ]);

      app.registry.for('settings-links').registerSetting({
        setting: 'settings-links.advanced',
        type: 'text',
        label: 'Registered setting',
        link: app.route('advanced'),
      });
      app.registry.for('settings-links').registerSetting({
        setting: 'settings-links.fallback',
        type: 'text',
        label: 'Registered fallback setting',
      });
    });

    it("uses an indexed setting's own link", async () => {
      const source = new GeneralSearchSource();
      await source.search('alpha setting', 10);

      const results = source['results'].get('alpha setting') ?? [];
      const result = results.find((r) => r.id.includes('own_link_setting'));

      expect(result).toBeDefined();
      expect(result!.link).toBe('/advanced');
    });

    it('falls back to the group link for an indexed setting', async () => {
      const source = new GeneralSearchSource();
      await source.search('fallback setting', 10);

      const results = source['results'].get('fallback setting') ?? [];
      const result = results.find((r) => r.id.includes('fallback_setting'));

      expect(result).toBeDefined();
      expect(result!.link).toBe('/mail');
    });

    it('falls back to the group link for a registered setting without a link', async () => {
      const source = new GeneralSearchSource();
      await source.search('registered fallback setting', 10);

      const results = source['results'].get('registered fallback setting') ?? [];
      const result = results.find((r) => r.tree.includes('Registered fallback setting'));

      expect(result).toBeDefined();
      expect(result!.link).toBe('/mail');
    });

    it('falls back to the page route when the group link is empty', async () => {
      const originalGroup = app.generalIndex.getGroup('core-basics');
      app.generalIndex.group('core-basics', {
        label: 'Basics',
        icon: { name: 'fas fa-cog' },
        link: '',
      });

      try {
        app.generalIndex.for('core-basics').add('settings', [
          {
            id: 'page_route_setting',
            label: 'Page route setting',
          },
        ]);

        const source = new GeneralSearchSource();
        await source.search('page route setting', 10);

        const results = source['results'].get('page route setting') ?? [];
        const result = results.find((r) => r.id.includes('page_route_setting'));

        expect(result).toBeDefined();
        expect(result!.link).toBe(app.route('basics'));
      } finally {
        if (originalGroup) app.generalIndex.group('core-basics', originalGroup);
      }
    });

    it('falls back to the extension route when there is no group link', async () => {
      const extensions = app.data.extensions as any;
      const previous = extensions['fallback-ext'];
      extensions['fallback-ext'] = {
        id: 'fallback-ext',
        version: '1.0.0',
        path: '/extensions/fallback-ext',
        icon: { name: 'fas fa-cube' },
        extra: { 'flarum-extension': { title: 'Fallback Ext' } },
      };

      try {
        app.generalIndex.for('fallback-ext').add('settings', [
          {
            id: 'extension_route_setting',
            label: 'Extension route setting',
          },
        ]);

        const source = new GeneralSearchSource();
        await source.search('extension route setting', 10);

        const results = source['results'].get('extension route setting') ?? [];
        const result = results.find((r) => r.id.includes('extension_route_setting'));

        expect(result).toBeDefined();
        expect(result!.link).toBe(app.route('extension', { id: 'fallback-ext' }));
      } finally {
        if (previous === undefined) {
          delete extensions['fallback-ext'];
        } else {
          extensions['fallback-ext'] = previous;
        }
      }
    });

    it("uses a registered setting's own link over the group link", async () => {
      const source = new GeneralSearchSource();
      await source.search('registered setting', 10);

      const results = source['results'].get('registered setting') ?? [];
      const result = results.find((r) => r.tree.includes('Registered setting'));

      expect(result).toBeDefined();
      expect(result!.link).toBe('/advanced');
    });

    it('falls back to the group link when the setting link is empty', async () => {
      const source = new GeneralSearchSource();
      await source.search('empty link setting', 10);

      const results = source['results'].get('empty link setting') ?? [];
      const result = results.find((r) => r.id.includes('empty_link_setting'));

      expect(result).toBeDefined();
      expect(result!.link).toBe('/mail');
    });
  });

  describe('permissions result links', () => {
    beforeAll(() => {
      app.generalIndex.group('permissions-links', {
        label: 'Permissions Links',
        icon: { name: 'fas fa-key' },
        link: app.route('mail'),
      });

      app.registry
        .for('permissions-links')
        .registerPermission(
          {
            permission: 'permission.ownLink',
            icon: 'fas fa-key',
            label: 'Alpha permission',
            link: app.route('advanced'),
          },
          'moderate'
        )
        .registerPermission(
          {
            permission: 'permission.fallback',
            icon: 'fas fa-key',
            label: 'Fallback permission',
          },
          'moderate'
        )
        .registerPermission(
          {
            permission: 'permission.emptyLink',
            icon: 'fas fa-key',
            label: 'Empty link permission',
            link: '',
          },
          'moderate'
        );
    });

    it("uses a registered permission's own link", async () => {
      const source = new GeneralSearchSource();
      await source.search('alpha permission', 10);

      const results = source['results'].get('alpha permission') ?? [];
      const result = results.find((r) => r.id.includes('permission.ownLink'));

      expect(result).toBeDefined();
      expect(result!.link).toBe('/advanced');
    });

    it('falls back to the group link for a registered permission', async () => {
      const source = new GeneralSearchSource();
      await source.search('fallback permission', 10);

      const results = source['results'].get('fallback permission') ?? [];
      const result = results.find((r) => r.id.includes('permission.fallback'));

      expect(result).toBeDefined();
      expect(result!.link).toBe('/mail');
    });

    it('falls back to the page route for a registered permission with an empty group link', async () => {
      const originalGroup = app.generalIndex.getGroup('core-permissions');
      app.generalIndex.group('core-permissions', {
        label: 'Permissions',
        icon: { name: 'fas fa-key' },
        link: '',
      });
      app.registry
        .for('core-permissions')
        .registerPermission({ permission: 'permission.pageRoute', icon: 'fas fa-key', label: 'Page route permission' }, 'moderate');

      try {
        const source = new GeneralSearchSource();
        await source.search('page route permission', 10);

        const results = source['results'].get('page route permission') ?? [];
        const result = results.find((r) => r.id.includes('permission.pageRoute'));

        expect(result).toBeDefined();
        expect(result!.link).toBe(app.route('permissions'));
      } finally {
        if (originalGroup) app.generalIndex.group('core-permissions', originalGroup);
      }
    });

    it('falls back to the extension route for a registered permission without a group link', async () => {
      const extensions = app.data.extensions as any;
      const previous = extensions['perm-ext'];
      extensions['perm-ext'] = {
        id: 'perm-ext',
        version: '1.0.0',
        path: '/extensions/perm-ext',
        icon: { name: 'fas fa-key' },
        extra: { 'flarum-extension': { title: 'Perm Ext' } },
      };
      app.registry
        .for('perm-ext')
        .registerPermission({ permission: 'permission.extRoute', icon: 'fas fa-key', label: 'Extension route permission' }, 'moderate');

      try {
        const source = new GeneralSearchSource();
        await source.search('extension route permission', 10);

        const results = source['results'].get('extension route permission') ?? [];
        const result = results.find((r) => r.id.includes('permission.extRoute'));

        expect(result).toBeDefined();
        expect(result!.link).toBe(app.route('extension', { id: 'perm-ext' }));
      } finally {
        if (previous === undefined) {
          delete extensions['perm-ext'];
        } else {
          extensions['perm-ext'] = previous;
        }
      }
    });

    it('falls back to the group link when the permission link is empty', async () => {
      const source = new GeneralSearchSource();
      await source.search('empty link permission', 10);

      const results = source['results'].get('empty link permission') ?? [];
      const result = results.find((r) => r.id.includes('permission.emptyLink'));

      expect(result).toBeDefined();
      expect(result!.link).toBe('/mail');
    });
  });
});
