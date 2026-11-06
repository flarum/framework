import app from 'flarum/admin/app';

/**
 * A model a connection's provider offers.
 */
export interface ProviderModel {
  id: string;
  name: string | null;
  deprecatedAt: string | null;
  retiresAt: string | null;
}

/**
 * A model a feature uses that's deprecated, no longer listed by its provider,
 * or that a call found gone.
 */
export interface ModelIssue {
  connection: string;
  model: string;
  status: 'deprecated' | 'missing' | 'failed';
  since: string;
  retiresAt: string | null;
  /** The keys of the features that use it. */
  features: string[];
}

export type ModelStatus = 'offered' | 'deprecated' | 'missing' | 'unknown';

/**
 * The model a provider's list offers under a name, as the server matches it:
 * Ollama serves `llama3.2` as `llama3.2:latest`, and an alias such as
 * `claude-x` names a dated release such as `claude-x-20261001`. Of several
 * releases an alias could mean, one still offered wins.
 */
export function findModel(models: ProviderModel[], id: string): ProviderModel | undefined {
  // Bedrock takes a model's or inference profile's ARN as well as its ID.
  const arn = /^arn:aws[a-z-]*:bedrock:[^:]*:[^:]*:(?:foundation-model|inference-profile)\/(.+)$/.exec(id);
  if (arn) id = arn[1];

  const exact = models.find((model) => model.id === id);

  if (exact) return exact;

  const escaped = id.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const alias = new RegExp(`^${escaped}-(\\d{8}|\\d{4}-\\d{2}-\\d{2}|\\d{3})$`);
  const matches = models.filter((model) => model.id === `${id}:latest` || alias.test(model.id));

  return matches.find((model) => !model.deprecatedAt && !model.retiresAt) || matches[0];
}

/**
 * Whether a provider's list offers a model. `unknown` when the list hasn't
 * loaded, or the provider can't be asked.
 */
export function modelStatus(models: ProviderModel[] | null | undefined, id: string): ModelStatus {
  // Bedrock takes ARNs for things it doesn't list: application inference
  // profiles, provisioned throughput and custom or imported models.
  const unlisted = id.startsWith('arn:') && !/^arn:aws[a-z-]*:bedrock:[^:]*:[^:]*:(?:foundation-model|inference-profile)\//.test(id);

  if (!models || unlisted) return 'unknown';

  const model = findModel(models, id);

  if (!model) return 'missing';

  return model.deprecatedAt || model.retiresAt ? 'deprecated' : 'offered';
}

export interface ModelList {
  status: 'loading' | 'loaded' | 'failed';
  /** Null when the connection's provider can't be asked for its models. */
  models: ProviderModel[] | null;
}

/**
 * Whether a connection's list can be loaded again: not while it's loading,
 * nor when its provider can't be asked for one.
 */
export function canReload(list: ModelList | undefined): boolean {
  if (!list) return true;

  return list.status === 'failed' || (list.status === 'loaded' && list.models !== null);
}

/**
 * The models each connection offers, loaded when first needed.
 */
export class ModelLists {
  protected lists: Record<string, ModelList> = {};

  get(connection: string): ModelList | undefined {
    return this.lists[connection];
  }

  load(connection: string, refresh = false): Promise<void> {
    if (!refresh && this.lists[connection] && this.lists[connection].status !== 'failed') return Promise.resolve();

    this.lists[connection] = { status: 'loading', models: this.lists[connection]?.models ?? null };

    return app
      .request<{ models: ProviderModel[] | null }>({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/ai/connections/' + encodeURIComponent(connection) + '/models',
        params: refresh ? { refresh: 1 } : undefined,
        // The field says the list couldn't load; no alert on top of that.
        errorHandler: (error) => console.error(error),
      })
      .then(({ models }) => {
        this.lists[connection] = { status: 'loaded', models };
      })
      .catch(() => {
        this.lists[connection] = { status: 'failed', models: null };
      })
      .finally(() => m.redraw());
  }
}
