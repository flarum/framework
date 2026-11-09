import { jest } from '@jest/globals';
import app from 'flarum/admin/app';
import AiPage from '../../../src/admin/components/AiPage';
import EditConnectionModal from '../../../src/admin/components/EditConnectionModal';
import { CONNECTIONS_SETTING, DEFAULT_SETTING } from '../../../src/admin/utils/settings';
import { bootAdmin } from './setup';

function page() {
  const page = new AiPage();
  (page as any).extension = { id: 'flarum-ai', extra: { 'flarum-extension': { title: 'AI' } } };
  page.oninit({ attrs: { id: 'flarum-ai' } } as any);

  return page;
}

function modal(attrs: Record<string, unknown> = {}) {
  const modal = new EditConnectionModal();
  modal.oninit({ attrs: { takenNames: [], onsave: () => {}, state: {}, ...attrs } } as any);

  return modal;
}

describe('the default connection', () => {
  beforeEach(() => bootAdmin());

  it('is the first connection added', () => {
    const show = jest.spyOn(app.modal, 'show').mockImplementation(() => {});
    const ai = page();
    ai.edit();

    const shown = show.mock.calls.at(-1) as any;
    shown[1].onsave('claude', { driver: 'anthropic', key: 'sk-1' });

    expect(ai.setting(DEFAULT_SETTING)()).toBe('claude');

    shown[1].onsave('local', { driver: 'ollama' });

    expect(ai.setting(DEFAULT_SETTING)()).toBe('claude');
  });

  it('is warned about when there is none', () => {
    bootAdmin({ [CONNECTIONS_SETTING]: '{"claude":{"driver":"anthropic"}}' });
    const ai = page();

    expect(ai.noDefaultAlert()).not.toBeNull();

    ai.setting(DEFAULT_SETTING)('claude');

    expect(ai.defaultConnection()).toBe('claude');
    expect(ai.noDefaultAlert()).toBeNull();
  });

  it('is gone with the connection', () => {
    bootAdmin({ [CONNECTIONS_SETTING]: '{"claude":{"driver":"anthropic"}}', [DEFAULT_SETTING]: 'claude' });
    const ai = page();

    ai.remove('claude');

    expect(ai.defaultConnection()).toBeNull();
  });
});

describe('testing a saved connection', () => {
  beforeEach(() => bootAdmin({ [CONNECTIONS_SETTING]: '{"claude":{"driver":"anthropic","model":"claude-x"}}' }));

  it("is possible while it's as saved", () => {
    expect(page().connectionChanged('claude')).toBe(false);
  });

  it('waits for changes to be saved', () => {
    const ai = page();

    ai.connections.claude = { ...ai.connections.claude, model: 'claude-y' };
    expect(ai.connectionChanged('claude')).toBe(true);

    ai.connections.claude = { driver: 'anthropic', model: 'claude-x', key: 'sk-new', hasKey: true };
    expect(ai.connectionChanged('claude')).toBe(true);

    ai.connections.added = { driver: 'ollama' };
    expect(ai.connectionChanged('added')).toBe(true);
  });
});

describe('usage against the monthly limit', () => {
  beforeEach(() => bootAdmin({ [CONNECTIONS_SETTING]: '{"claude":{"driver":"anthropic","monthly_tokens":1000}}' }));

  it('is shown as a meter', () => {
    (app.data as any)['flarum-ai'].monthlyUsage = { claude: 900 };
    const ai = page();

    const usage = ai.connectionUsage('claude', ai.connections.claude) as any;

    expect(usage.attrs.className).toContain('AiPage-connection-usage--high');
  });

  it("isn't shown for a connection without a limit or usage", () => {
    const ai = page();

    expect(ai.connectionUsage('claude', { driver: 'anthropic' })).toBe(false);
  });
});

describe('a connection not saved yet', () => {
  beforeEach(() => bootAdmin());

  it('starts with the provider chosen from the list', () => {
    expect(modal({ driver: 'ollama' }).driver()).toBe('ollama');
  });

  it('is asked for its models only once enough is typed in', () => {
    const form = modal();

    form.driver('anthropic');
    form.name('claude');
    expect(form.canAsk()).toBe(false);

    form.key('sk-1');
    expect(form.canAsk()).toBe(true);

    form.driver('openai-compatible');
    form.key('');
    expect(form.canAsk()).toBe(false);

    form.url('https://llm.test/v1');
    expect(form.canAsk()).toBe(true);
  });

  it('is sent as typed in, leaving the server to use the saved key', () => {
    const form = modal({ name: 'claude', connection: { driver: 'anthropic', url: 'https://proxy.test/v1', model: 'claude-x', hasKey: true } });

    expect(form.candidateFor()).toEqual({ driver: 'anthropic', url: 'https://proxy.test/v1', model: 'claude-x' });
    expect(form.candidateFor(true)).toEqual({ driver: 'anthropic', url: 'https://proxy.test/v1' });

    form.removeKey = true;
    form.key('sk-new');

    expect(form.candidateFor()).toEqual({ driver: 'anthropic', url: 'https://proxy.test/v1', model: 'claude-x', key: 'sk-new', removeKey: true });
  });

  it("isn't tested while the form has a problem", () => {
    const form = modal();
    const start = jest.spyOn(form.test, 'start');

    form.name('123');
    form.runTest();

    expect(start).not.toHaveBeenCalled();
    expect(form.invalid).toBe('name');
  });
});
