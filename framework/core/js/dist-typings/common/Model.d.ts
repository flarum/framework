import { FlarumRequestOptions } from './Application';
import Store, { ApiPayloadSingle, ApiResponseSingle, MetaInformation } from './Store';
export interface ModelIdentifier {
    type: string;
    id: string;
}
export interface ModelAttributes {
    [key: string]: unknown;
}
export interface ModelRelationships {
    [relationship: string]: {
        data: ModelIdentifier | ModelIdentifier[];
    };
}
export interface UnsavedModelData {
    type?: string;
    attributes?: ModelAttributes;
    relationships?: ModelRelationships;
}
export interface SavedModelData {
    type: string;
    id: string;
    attributes?: ModelAttributes;
    relationships?: ModelRelationships;
}
export type ModelData = UnsavedModelData | SavedModelData;
export interface SaveRelationships {
    [relationship: string]: null | Model | Model[];
}
export interface SaveAttributes {
    [key: string]: unknown;
    relationships?: SaveRelationships;
}
/**
 * The `Model` class represents a local data resource. It provides methods to
 * persist changes via the API.
 */
export default abstract class Model {
    /**
     * The resource object from the API.
     */
    data: ModelData;
    /**
     * The time at which the model's data was last updated. Watching the value
     * of this property is a fast way to retain/cache a subtree if data hasn't
     * changed.
     */
    freshness: Date;
    /**
     * Whether or not the resource exists on the server.
     */
    exists: boolean;
    /**
     * The data store that this resource should be persisted to.
     */
    protected store: Store;
    /**
     * @param data A resource object from the API.
     * @param store The data store that this model should be persisted to.
     */
    constructor(data?: ModelData, store?: Store);
    /**
     * Get the model's ID.
     *
     * @final
     */
    id(): string | undefined;
    /**
     * Get one of the model's attributes.
     *
     * @final
     */
    attribute<T = unknown>(attribute: string): T;
    /**
     * Merge new data into this model locally.
     *
     * @param data A resource object to merge into this model
     */
    pushData(data: ModelData | {
        relationships?: SaveRelationships;
    }): this;
    /**
     * Merge new attributes into this model locally.
     *
     * @param attributes The attributes to merge.
     */
    pushAttributes(attributes: ModelAttributes): void;
    /**
     * Merge new attributes into this model, both locally and with persistence.
     *
     * @param attributes The attributes to save. If a 'relationships' key
     *     exists, it will be extracted and relationships will also be saved.
     */
    save(attributes: SaveAttributes, options?: Omit<FlarumRequestOptions<ApiPayloadSingle>, 'url'> & {
        meta?: MetaInformation;
    }): Promise<ApiResponseSingle<this>>;
    /**
     * Send a request to delete the resource.
     *
     * @param body Data to send along with the DELETE request.
     */
    delete(body?: FlarumRequestOptions<void>['body'], options?: Omit<FlarumRequestOptions<void>, 'url'>): Promise<void>;
    /**
     * Construct a path to the API endpoint for this resource.
     */
    protected apiEndpoint(): string;
    protected copyData(): ModelData;
    protected rawRelationship<M extends Model>(relationship: string): undefined | ModelIdentifier;
    protected rawRelationship<M extends Model[]>(relationship: string): undefined | ModelIdentifier[];
    /**
     * Generate a function which returns the value of the given attribute.
     *
     * @param transform A function to transform the attribute value
     */
    static attribute<T>(name: string): () => T;
    static attribute<T, O = unknown>(name: string, transform: (attr: O) => T): () => T;
    /**
     * Generate a function which returns the value of the given has-one
     * relationship.
     *
     * @return false if no information about the
     *     relationship exists; undefined if the relationship exists but the model
     *     has not been loaded; or the model if it has been loaded.
     */
    static hasOne<M extends Model>(name: string): () => M | false;
    static hasOne<M extends Model | null>(name: string): () => M | null | false;
    /**
     * Generate a function which returns the value of the given has-many
     * relationship.
     *
     * @return false if no information about the relationship
     *     exists; an array if it does, containing models if they have been
     *     loaded, and undefined for those that have not.
     */
    static hasMany<M extends Model>(name: string): () => (M | undefined)[] | false;
    /**
     * Transform the given value into a Date object.
     */
    static transformDate(value: string): Date;
    static transformDate(value: string | null): Date | null;
    static transformDate(value: string | undefined): Date | undefined;
    static transformDate(value: string | null | undefined): Date | null | undefined;
    /**
     * Get a resource identifier object for the given model.
     */
    protected static getIdentifier(model: Model): ModelIdentifier;
}
