import app from 'flarum/admin/app';
import EditConnectionModal from '../../../src/admin/components/EditConnectionModal';
import { bootAdmin } from './setup';

beforeEach(() => bootAdmin());

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

describe('a Bedrock connection', () => {
  it('asks for a region and a guardrail, not an address', () => {
    const form = modal();

    form.driver('bedrock');
    const fields = form.fields();

    expect(fields.has('region')).toBe(true);
    expect(fields.has('guardrail')).toBe(true);
    expect(fields.has('url')).toBe(false);

    form.driver('anthropic');
    expect(form.fields().has('region')).toBe(false);
    expect(form.fields().has('url')).toBe(true);
  });

  it('needs a region, and a key unless config.php lends the server its AWS credentials', () => {
    const form = modal();

    form.name('aws');
    form.driver('bedrock');
    expect(form.validate()?.field).toBe('region');

    form.region('Frankfurt');
    expect(form.validate()?.message).toBe('flarum-ai.admin.connections.region_invalid_message');

    form.region('eu-central-1');
    expect(form.validate()?.field).toBe('key');

    (app.data as any)['flarum-ai'].keyOptional = ['bedrock'];
    expect(form.validate()).toBeNull();

    form.guardrailId('gr1');
    form.guardrailVersion('latest');
    expect(form.validate()?.field).toBe('guardrail');

    form.guardrailVersion('');
    expect(form.validate()).toBeNull();
  });

  it('keeps offering its provider once the AWS SDK is gone, so it can still be changed', () => {
    (app.data as any)['flarum-ai'].drivers = ['anthropic'];

    expect(modal({ name: 'aws', connection: { driver: 'bedrock', region: 'eu-central-1', hasKey: true } }).driverOptions()).toHaveProperty('bedrock');
    expect(modal().driverOptions()).not.toHaveProperty('bedrock');
  });

  it('is saved with its region and guardrail', () => {
    let saved: any = null;
    const form = modal({ onsave: (name: string, connection: any) => (saved = [name, connection]) });
    (form as any).hide = () => {};

    form.name('aws');
    form.driver('bedrock');
    form.url('https://ignored.test');
    form.region(' eu-central-1 ');
    form.guardrailId(' gr1 ');
    form.key('ABSKkey');
    form.onsubmit({ preventDefault() {} } as any);

    expect(saved).toEqual(['aws', { driver: 'bedrock', region: 'eu-central-1', guardrail_id: 'gr1', key: 'ABSKkey' }]);
  });

  it('keeps listing its models after a look at another provider and back', () => {
    const form = modal({ name: 'aws', listedAs: 'aws', lists: {}, connection: { driver: 'bedrock', region: 'eu-central-1', hasKey: true } });

    expect(form.listsSavedModels()).toBe(true);

    form.driver('anthropic');
    form.url('https://typed.test');
    form.driver('bedrock');

    expect(form.listsSavedModels()).toBe(true);
  });

  it('only counts a saved key while the region is the same', () => {
    const form = modal({ name: 'aws', connection: { driver: 'bedrock', region: 'eu-central-1', hasKey: true } });

    expect(form.savedKeyApplies()).toBe(true);

    form.region('us-east-1');
    expect(form.savedKeyApplies()).toBe(false);
  });

  it('says what to install when the AWS SDK is missing', () => {
    (app.data as any)['flarum-ai'].awsSdk = false;

    expect(modal().fields().has('awsSdk')).toBe(true);

    (app.data as any)['flarum-ai'].awsSdk = true;

    expect(modal().fields().has('awsSdk')).toBe(false);
  });
});
