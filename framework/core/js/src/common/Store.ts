import app from '../common/app';
import { FlarumRequestOptions } from './Application';
import { fireDeprecationWarning } from './helpers/fireDebugWarning';
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
  filter?:
    | {
        q: string;
      }
    | Record<string, string>;
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

export type ApiResponseSingle<M extends Model> = M & { payload: ApiPayloadSingle };
export type ApiResponsePlural<M extends Model> = M[] & { payload: ApiPayloadPlural };
export type ApiResponse<M extends Model> = ApiResponseSingle<M> | ApiResponsePlural<M>;

interface ApiQueryRequestOptions<ResponseType> extends Omit<FlarumRequestOptions<ResponseType>, 'url'> {}

interface StoreData {
  [type: string]: Partial<Record<string, Model>>;
}

export function payloadIsPlural(payload: ApiPayload): payload is ApiPayloadPlural {
  return Array.isArray((payload as ApiPayloadPlural).data);
}

/**
 * The `Store` class defines a local data store, and provides methods to
 * retrieve data from the API.
 */
export default class Store {
  /**
   * The local data store. A tree of resource types to IDs, such that
   * accessing data[type][id] will return the model for that type/ID.
   */
  protected data: StoreData = {};

  /**
   * The model registry. A map of resource types to the model class that
   * should be used to represent resources of that type.
   */
  models: Record<string, { new (): Model }>;

  constructor(models: Record<string, { new (): Model }>) {
    this.models = models;
  }

  /**
   * Push resources contained within an API payload into the store.
   *
   * @return The model(s) representing the resource(s) contained
   *     within the 'data' key of the payload.
   */
  pushPayload<M extends Model>(payload: ApiPayloadSingle): ApiResponseSingle<M>;
  pushPayload<Ms extends Model[]>(payload: ApiPayloadPlural): ApiResponsePlural<Ms[number]>;
  pushPayload<M extends Model | Model[]>(payload: ApiPayload): ApiResponse<FlatArray<M, 1>> {
    if (payload.included) payload.included.map(this.pushObject.bind(this));

    const models = payload.data instanceof Array ? payload.data.map((o) => this.pushObject(o, false)) : this.pushObject(payload.data, false);
    const result = models as ApiResponse<FlatArray<M, 1>>;

    // Attach the original payload to the model that we give back. This is
    // useful to consumers as it allows them to access meta information
    // associated with their request.
    result.payload = payload;

    return result;
  }

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
  pushObject<M extends Model>(data: SavedModelData, allowUnregistered = true): M | null {
    if (!this.models[data.type]) {
      if (!allowUnregistered) {
        setTimeout(() =>
          fireDeprecationWarning(`Pushing object of type \`${data.type}\` not allowed, as type not yet registered in the store.`, '3206')
        );
      }

      return null;
    }

    const type = (this.data[data.type] = this.data[data.type] || {});

    // Necessary for TS to narrow correctly.
    const curr = type[data.id] as M;
    const instance = curr ? curr.pushData(data) : this.createRecord<M>(data.type, data);

    type[data.id] = instance;
    instance.exists = true;

    return instance;
  }

  /**
   * Make a request to the API to find record(s) of a specific type.
   */
  async find<M extends Model>(type: string, params?: ApiQueryParamsSingle): Promise<ApiResponseSingle<M>>;
  async find<Ms extends Model[]>(type: string, params?: ApiQueryParamsPlural): Promise<ApiResponsePlural<Ms[number]>>;
  async find<M extends Model>(
    type: string,
    id: string,
    params?: ApiQueryParamsSingle,
    options?: ApiQueryRequestOptions<ApiPayloadSingle>
  ): Promise<ApiResponseSingle<M>>;
  async find<Ms extends Model[]>(
    type: string,
    ids: string[],
    params?: ApiQueryParamsPlural,
    options?: ApiQueryRequestOptions<ApiPayloadPlural>
  ): Promise<ApiResponsePlural<Ms[number]>>;
  async find<M extends Model | Model[]>(
    type: string,
    idOrParams: undefined | string | string[] | ApiQueryParams,
    query: ApiQueryParams = {},
    options: ApiQueryRequestOptions<M extends Array<infer _T> ? ApiPayloadPlural : ApiPayloadSingle> = {}
  ): Promise<ApiResponse<FlatArray<M, 1>>> {
    let params = query;
    let url = app.forum.attribute('apiUrl') + '/' + type;

    if (idOrParams instanceof Array) {
      url += '?filter[id]=' + idOrParams.join(',');
    } else if (typeof idOrParams === 'object') {
      params = idOrParams;
    } else if (idOrParams) {
      url += '/' + idOrParams;
    }

    return app
      .request<M extends Array<infer _T> ? ApiPayloadPlural : ApiPayloadSingle>({
        method: 'GET',
        url,
        params,
        ...options,
      })
      .then((payload) => {
        if (payloadIsPlural(payload)) {
          return this.pushPayload<FlatArray<M, 1>[]>(payload);
        } else {
          return this.pushPayload<FlatArray<M, 1>>(payload);
        }
      });
  }

  /**
   * Get a record from the store by ID.
   */
  getById<M extends Model>(type: string, id: string): M | undefined {
    return this.data?.[type]?.[id] as M;
  }

  /**
   * Get a record from the store by the value of a model attribute.
   *
   * @param type The resource type.
   * @param key The name of the method on the model.
   * @param value The value of the model attribute.
   */
  getBy<M extends Model, T = unknown>(type: string, key: keyof M, value: T): M | undefined {
    // @ts-expect-error No way to do this safely, unfortunately.
    return this.all(type).filter((model) => model[key]() === value)[0] as M;
  }

  /**
   * Get all loaded records of a specific type.
   */
  all<M extends Model>(type: string): M[] {
    const records = this.data[type];

    return records ? (Object.values(records) as M[]) : [];
  }

  /**
   * Remove the given model from the store.
   */
  remove(model: Model): void {
    delete this.data[model.data.type as string][model.id() as string];
  }

  /**
   * Create a new record of the given type.
   *
   * @param type The resource type
   * @param data Any data to initialize the model with
   */
  createRecord<M extends Model>(type: string, data: ModelData = {}): M {
    data.type = data.type || type;

    // @ts-expect-error this will complain about initializing abstract models,
    // but we can safely assume that all models registered with the store are
    // not abstract.
    return new this.models[type](data, this);
  }
}
