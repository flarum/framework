import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import adminExtenders from '../../../src/admin/extend';

beforeAll(() => {
  bootstrapAdmin({
    extensions: {
      'flarum-messages': {
        id: 'flarum-messages',
        version: '1.0.0',
        description: '',
        icon: { name: 'fas fa-envelope-open' },
        links: {},
        extra: { 'flarum-extension': { title: 'Messages' } },
      },
    },
    settings: { extensions_enabled: JSON.stringify(['flarum-messages']) },
    dbDriver: 'sqlite',
  } as any);

  // Settings and sections reach the registry as the app boots.
  adminExtenders.forEach((extender) => extender.extend(app as any, { name: 'flarum-messages', exports: {} } as any));
  app.boot();
});

const sectionOf = (setting: string) => app.registry.getSettingLocation('flarum-messages', { setting } as any).section;

describe("the extension's settings page", () => {
  it('groups the group conversation settings in a section of their own', () => {
    const recipients = sectionOf('flarum-messages.max_recipients');
    const names = sectionOf('flarum-messages.allow_group_names');

    expect(recipients?.id).toBe('groups');
    expect(names?.id).toBe('groups');
    expect(recipients?.label).toBeTruthy();
  });
});
