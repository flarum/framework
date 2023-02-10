import { FlarumRequestOptions } from './Application';
import Model, { ModelData, SavedModelData } from './Model';
export interface MetaInformation {
    [key: string]: any;
}
export interface ApiQueryParamsSingle {
    fields?: string[];
    include?: string;
    bySlug?: boolean;
    meta?: MetaInformation;
}
export interface ApiQueryParamsPlural {
    fields?: string[];
    include?: string;
    filter?: {
        q: string;
    } | Record<string, string>;
    page?: {
        near?: number;
        offset?: number;
        number?: number;
        limit?: number;
        size?: number;
    };
    sort?: string;
    meta?: MetaInformation;
}
export type ApiQueryParams = ApiQueryParamsPlural | ApiQueryParamsSingle;
export interface ApiPayloadSingle {
    data: SavedModelData;
    included?: SavedModelData[];
    meta?: MetaInformation;
}
export interface ApiPayloadPlural {
    data: SavedModelData[];
    included?: SavedModelData[];
    links?: {
        first: string;
        next?: string;
        prev?: string;
    };
    meta?: MetaInformation;
}
export type ApiPayload = ApiPayloadSingle | ApiPayloadPlural;
export type ApiResponseSingle<M extends Model> = M & {
    payload: ApiPayloadSingle;
};
export type ApiResponsePlural<M extends Model> = M[] & {
    payload: ApiPayloadPlural;
};
export type ApiResponse<M extends Model> = ApiResponseSingle<M> | ApiResponsePlural<M>;
interface ApiQueryRequestOptions<ResponseType> extends Omit<FlarumRequestOptions<ResponseType>, 'url'> {
}
interface StoreData {
    [type: string]: Partial<Record<string, Model>>;
}
export declare function payloadIsPlural(payload: ApiPayload): payload is ApiPayloadPlural;
/**
 * The `Store` class defines a local data store, and provides methods to
 * retrieve data from the API.
 */
export default class Store {
    /**
     * The local data store. A tree of resource types to IDs, such that
     * accessing data[type][id] will return the model for that type/ID.
     */
    protected data: StoreData;
    /**
     * The model registry. A map of resource types to the model class that
     * should be used to represent resources of that type.
     */
    models: Record<string, {
        new (): Model;
    }>;
    constructor(models: Record<string, {
        new (): Model;
    }>);
    /**
     * Push resources contained within an API payload into the store.
     *
     * @return The model(s) representing the resource(s) contained
     *     within the 'data' key of the payload.
     */
    pushPayload<M extends Model>(payload: ApiPayloadSingle): ApiResponseSingle<M>;
    pushPayload<Ms extends Model[]>(payload: ApiPayloadPlural): ApiResponsePlural<Ms[number]>;
    /**
     * Create a model to represent a resource object (or update an existing one),
     * and push it into the store.
     *
     * @param data The resource object
     * @return The model, or null if no model class has been
     *     registered for this resource type.
     */
    pushObject<M extends Model>(data: SavedModelData): M | null;
    pushObject<M extends Model>(data: SavedModelData, allowUnregistered: false): M;
    /**
     * Make a request to the API to find record(s) of a specific type.
     */
    find<M extends Model>(type: string, params?: ApiQueryParamsSingle): Promise<ApiResponseSingle<M>>;
    find<Ms extends Model[]>(type: string, params?: ApiQueryParamsPlural): Promise<ApiResponsePlural<Ms[number]>>;
    find<M extends Model>(type: string, id: string, params?: ApiQueryParamsSingle, options?: ApiQueryRequestOptions<ApiPayloadSingle>): Promise<ApiResponseSingle<M>>;
    find<Ms extends Model[]>(type: string, ids: string[], params?: ApiQueryParamsPlural, options?: ApiQueryRequestOptions<ApiPayloadPlural>): Promise<ApiResponsePlural<Ms[number]>>;
    /**
     * Get a record from the store by ID.
     */
    getById<M extends Model>(type: string, id: string): M | undefined;
    /**
     * Get a record from the store by the value of a model attribute.
     *
     * @param type The resource type.
     * @param key The name of the method on the model.
     * @param value The value of the model attribute.
     */
    getBy<M extends Model, T = unknown>(type: string, key: keyof M, value: T): M | undefined;
    /**
     * Get all loaded records of a specific type.
     */
    all<M extends Model>(type: string): M[];
    /**
     * Remove the given model from the store.
     */
    remove(model: Model): void;
    /**
     * Create a new record of the given type.
     *
     * @param type The resource type
     * @param data Any data to initialize the model with
     */
    createRecord<M extends Model>(type: string, data?: ModelData): M;
}
export {};
