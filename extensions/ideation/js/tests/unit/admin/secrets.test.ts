import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import { jest } from '@jest/globals';
import adminExtenders from '../../../src/admin/extend';

beforeAll(() => {
  bootstrapAdmin({
    extensions: {
      'flarum-ideation': {
        id: 'flarum-ideation',
        version: '1.0.0',
        description: '',
        icon: { name: 'fas fa-lightbulb' },
        links: {},
        extra: { 'flarum-extension': { title: 'Ideation' } },
      },
    },
    // As the server sends them: blanked, with only whether each is saved.
    settings: { extensions_enabled: JSON.stringify(['flarum-ideation']), 'flarum-ideation.github.token': '', 'flarum-ideation.github.secret': '' },
    ideationGithubSecrets: { token: true, secret: false },
    dbDriver: 'sqlite',
  } as any);

  adminExtenders.forEach((extender) => extender.extend(app as any, { name: 'flarum-ideation', exports: {} } as any));
  app.boot();
});

/** The settings page, rendered, without going through the router. */
function renderSettings() {
  const page = Object.create(ExtensionPage.prototype) as any;
  Object.assign(page, { settings: {}, settingLabels: {}, refreshAfterSaving: [] });

  const custom = app.registry
    .getSettings('flarum-ideation')
    .filter((entry: any) => typeof entry === 'function')
    .map((entry: any) => page.buildSettingComponent(entry));

  const root = document.createElement('div');
  m.render(root, custom);

  return { page, root };
}

describe('the GitHub token and webhook secret', () => {
  it('render without their values, with a way to remove a saved one', () => {
    const { page, root } = renderSettings();
    const inputs = root.querySelectorAll('input[type="password"]');

    expect(inputs).toHaveLength(2);
    expect((inputs[0] as HTMLInputElement).value).toBe('');
    expect(inputs[0].getAttribute('placeholder')).toBeTruthy();
    expect(inputs[1].getAttribute('placeholder')).toBeNull();
    expect(root.querySelectorAll('.IdeationSecretSetting .Button--link')).toHaveLength(1);

    // Opening the page does not count as a change to save.
    expect(page.dirty()).toEqual({});
  });

  it('remove the saved token by sending null', async () => {
    const request = jest.spyOn(app, 'request').mockResolvedValue(undefined as any);
    const { root } = renderSettings();

    (root.querySelector('.IdeationSecretSetting .Button--link') as HTMLElement).click();
    await new Promise((r) => setTimeout(r));

    expect(request).toHaveBeenCalledWith(expect.objectContaining({ body: { 'flarum-ideation.github.token': null } }));
    expect((app.data as any).ideationGithubSecrets.token).toBe(false);
  });
});
