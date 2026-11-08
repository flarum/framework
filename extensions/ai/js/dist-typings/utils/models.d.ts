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
export declare function findModel(models: ProviderModel[], id: string): ProviderModel | undefined;
/**
 * Whether a provider's list offers a model. `unknown` when the list hasn't
 * loaded, or the provider can't be asked.
 */
export declare function modelStatus(models: ProviderModel[] | null | undefined, id: string): ModelStatus;
export interface ModelList {
    status: 'loading' | 'loaded' | 'failed';
    /** Null when the connection's provider can't be asked for its models. */
    models: ProviderModel[] | null;
}
/**
 * Whether a connection's list can be loaded again: not while it's loading,
 * nor when its provider can't be asked for one.
 */
export declare function canReload(list: ModelList | undefined): boolean;
/**
 * The models each connection offers, loaded when first needed.
 */
export declare class ModelLists {
    protected lists: Record<string, ModelList>;
    get(connection: string): ModelList | undefined;
    load(connection: string, refresh?: boolean): Promise<void>;
}
