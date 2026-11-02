import EditConnectionModal from '../../../src/admin/components/EditConnectionModal';
import { bootAdmin } from './setup';

beforeAll(() => bootAdmin());

function modal(attrs: Record<string, unknown> = {}) {
  const modal = new EditConnectionModal();
  modal.oninit({ attrs: { takenNames: ['taken'], onsave: () => {}, state: {}, ...attrs } } as any);

  return modal;
}

describe('the connection form', () => {
  it('checks names as the server does', () => {
    const form = modal();

    form.name('123');
    expect(form.validate()?.field).toBe('name');

    form.name('taken');
    expect(form.validate()?.message).toBe('flarum-ai.admin.edit_connection.name_taken_message');

    form.name('claude');
    form.key('sk-1');
    expect(form.validate()).toBeNull();
  });

  it('needs an address for a compatible server, and a key where the provider needs one', () => {
    const form = modal();

    form.name('own');
    form.driver('openai-compatible');
    expect(form.validate()?.field).toBe('url');

    form.driver('anthropic');
    expect(form.validate()?.field).toBe('key');

    form.driver('ollama');
    expect(form.validate()).toBeNull();
  });

  it('refuses a monthly limit that is not a whole number', () => {
    const form = modal();

    form.name('claude');
    form.key('sk-1');
    form.monthlyTokens('1e3');

    expect(form.validate()?.field).toBe('monthlyTokens');
  });

  it('only counts a saved key while the provider and address are the same', () => {
    const form = modal({ name: 'claude', connection: { driver: 'anthropic', url: 'https://proxy.test/v1', hasKey: true } });

    expect(form.savedKeyApplies()).toBe(true);
    expect(form.validate()).toBeNull();

    form.url('https://PROXY.test/v1/');
    expect(form.savedKeyApplies()).toBe(true);

    form.url('https://elsewhere.test/v1');
    expect(form.savedKeyApplies()).toBe(false);
    expect(form.validate()?.field).toBe('key');

    form.url('https://proxy.test/v1');
    form.driver('openai-compatible');
    expect(form.savedKeyApplies()).toBe(false);
  });
});
