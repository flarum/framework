import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import { jest } from '@jest/globals';
import adminExtenders, { rememberSavedSecrets } from '../../../src/admin/extend';

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
  rememberSavedSecrets();
  app.boot();
});

/** The settings page, rendered, without going through the router. */
function renderSettings(page = Object.assign(Object.create(ExtensionPage.prototype), { settings: {}, settingLabels: {}, refreshAfterSaving: [] })) {
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

  it('show a newly saved secret as saved, without keeping it in the page', async () => {
    jest.spyOn(app, 'request').mockResolvedValue(undefined as any);
    const { page } = renderSettings();

    page.setting('flarum-ideation.github.secret')('a-new-webhook-secret');
    await page.saveSettings({ preventDefault() {} });

    expect((app.data as any).ideationGithubSecrets.secret).toBe(true);
    expect(app.data.settings['flarum-ideation.github.secret']).toBe('');
    expect(page.dirty()).toEqual({});

    const { root } = renderSettings(page);
    const secret = root.querySelectorAll('input[type="password"]')[1] as HTMLInputElement;
    expect(secret.value).toBe('');
    expect(secret.getAttribute('placeholder')).toBeTruthy();
    // The secret's own Remove button (the token was removed above).
    expect(root.querySelectorAll('.IdeationSecretSetting')[1].querySelector('.Button--link')).not.toBeNull();
    expect(root.innerHTML).not.toContain('a-new-webhook-secret');
  });

  it('do not show a secret as saved when the save fails', async () => {
    (app.data as any).ideationGithubSecrets.secret = false;
    jest.spyOn(app, 'request').mockRejectedValue(new Error('offline') as never);
    const { page } = renderSettings();

    page.setting('flarum-ideation.github.secret')('another-secret');
    await page.saveSettings({ preventDefault() {} });

    expect((app.data as any).ideationGithubSecrets.secret).toBe(false);
    expect(page.dirty()).toEqual({ 'flarum-ideation.github.secret': 'another-secret' });
  });
});
