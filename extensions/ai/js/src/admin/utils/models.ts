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
 * Whether a provider's list offers a model. `unknown` when the list hasn't
 * loaded, or the provider can't be asked.
 */
export function modelStatus(models: ProviderModel[] | null | undefined, id: string): ModelStatus {
  if (!models) return 'unknown';

  const model = models.find((model) => model.id === id);

  if (!model) return 'missing';

  return model.deprecatedAt || model.retiresAt ? 'deprecated' : 'offered';
}

export interface ModelList {
  status: 'loading' | 'loaded' | 'failed';
  /** Null when the connection's provider can't be asked for its models. */
  models: ProviderModel[] | null;
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
