import app from '../common/app';
import { FlarumRequestOptions } from './Application';
import { fireDeprecationWarning } from './helpers/fireDebugWarning';
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
  [relationship: string]: Model | Model[];
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
  data: ModelData = {};

  /**
   * The time at which the model's data was last updated. Watching the value
   * of this property is a fast way to retain/cache a subtree if data hasn't
   * changed.
   */
  freshness: Date = new Date();

  /**
   * Whether or not the resource exists on the server.
   */
  exists: boolean = false;

  /**
   * The data store that this resource should be persisted to.
   */
  protected store: Store;

  /**
   * @param data A resource object from the API.
   * @param store The data store that this model should be persisted to.
   */
  constructor(data: ModelData = {}, store = app.store) {
    this.data = data;
    this.store = store;
  }

  /**
   * Get the model's ID.
   *
   * @final
   */
  id(): string | undefined {
    return 'id' in this.data ? this.data.id : undefined;
  }

  /**
   * Get one of the model's attributes.
   *
   * @final
   */
  attribute<T = unknown>(attribute: string): T {
    return this.data?.attributes?.[attribute] as T;
  }

  /**
   * Merge new data into this model locally.
   *
   * @param data A resource object to merge into this model
   */
  pushData(data: ModelData | { relationships?: SaveRelationships }): this {
    if ('id' in data) {
      (this.data as SavedModelData).id = data.id;
    }

    if ('type' in data) {
      this.data.type = data.type;
    }

    if ('attributes' in data) {
      this.data.attributes ||= {};

      // @deprecated
      // Filter out relationships that got in by accident.
      for (const key in data.attributes) {
        const val = data.attributes[key];
        if (val && val instanceof Model) {
          fireDeprecationWarning('Providing models as attributes to `Model.pushData()` or `Model.pushAttributes()` is deprecated.', '3249');
          delete data.attributes[key];
          data.relationships ||= {};
          data.relationships[key] = { data: Model.getIdentifier(val) };
        }
      }

      Object.assign(this.data.attributes, data.attributes);
    }

    if ('relationships' in data) {
      const relationships = this.data.relationships ?? {};

      // For every relationship field, we need to check if we've
      // been handed a Model instance. If so, we will convert it to a
      // relationship data object.
      for (const r in data.relationships) {
        const relationship = data.relationships[r];

        let identifier: ModelRelationships[string];
        if (relationship instanceof Model) {
          identifier = { data: Model.getIdentifier(relationship) };
        } else if (relationship instanceof Array) {
          identifier = { data: relationship.map(Model.getIdentifier) };
        } else {
          identifier = relationship;
        }

        data.relationships[r] = identifier;
        relationships[r] = identifier;
      }

      this.data.relationships = relationships;
    }

    // Now that we've updated the data, we can say that the model is fresh.
    // This is an easy way to invalidate retained subtrees etc.
    this.freshness = new Date();

    return this;
  }

  /**
   * Merge new attributes into this model locally.
   *
   * @param attributes The attributes to merge.
   */
  pushAttributes(attributes: ModelAttributes) {
    this.pushData({ attributes });
  }

  /**
   * Merge new attributes into this model, both locally and with persistence.
   *
   * @param attributes The attributes to save. If a 'relationships' key
   *     exists, it will be extracted and relationships will also be saved.
   */
  save(
    attributes: SaveAttributes,
    options: Omit<FlarumRequestOptions<ApiPayloadSingle>, 'url'> & { meta?: MetaInformation } = {}
  ): Promise<ApiResponseSingle<this>> {
    const data: ModelData & { id?: string } = {
      type: this.data.type,
      attributes,
    };

    if ('id' in this.data) {
      data.id = this.data.id;
    }

    // If a 'relationships' key exists, extract it from the attributes hash and
    // set it on the top-level data object instead. We will be sending this data
    // object to the API for persistence.
    if (attributes.relationships) {
      data.relationships = {};

      for (const key in attributes.relationships) {
        const model = attributes.relationships[key];

        data.relationships[key] = {
          data: model instanceof Array ? model.map(Model.getIdentifier) : Model.getIdentifier(model),
        };
      }

      delete attributes.relationships;
    }

    // Before we update the model's data, we should make a copy of the model's
    // old data so that we can revert back to it if something goes awry during
    // persistence.
    const oldData = this.copyData();

    this.pushData(data);

    const request = {
      data,
      meta: options.meta || undefined,
    };

    return app
      .request<ApiPayloadSingle>({
        method: this.exists ? 'PATCH' : 'POST',
        url: app.forum.attribute('apiUrl') + this.apiEndpoint(),
        body: request,
        ...options,
      })
      .then(
        // If everything went well, we'll make sure the store knows that this
        // model exists now (if it didn't already), and we'll push the data that
        // the API returned into the store.
        (payload) => {
          return this.store.pushPayload<this>(payload);
        },

        // If something went wrong, though... good thing we backed up our model's
        // old data! We'll revert to that and let others handle the error.
        (err: Error) => {
          this.pushData(oldData);
          m.redraw();
          throw err;
        }
      );
  }

  /**
   * Send a request to delete the resource.
   *
   * @param body Data to send along with the DELETE request.
   */
  delete(body: FlarumRequestOptions<void>['body'] = {}, options: Omit<FlarumRequestOptions<void>, 'url'> = {}): Promise<void> {
    if (!this.exists) return Promise.resolve();

    return app
      .request({
        method: 'DELETE',
        url: app.forum.attribute('apiUrl') + this.apiEndpoint(),
        body,
        ...options,
      })
      .then(() => {
        this.exists = false;

        this.store.remove(this);
      });
  }

  /**
   * Construct a path to the API endpoint for this resource.
   */
  protected apiEndpoint(): string {
    return '/' + this.data.type + ('id' in this.data ? '/' + this.data.id : '');
  }

  protected copyData(): ModelData {
    return JSON.parse(JSON.stringify(this.data));
  }

  protected rawRelationship<M extends Model>(relationship: string): undefined | ModelIdentifier;
  protected rawRelationship<M extends Model[]>(relationship: string): undefined | ModelIdentifier[];
  protected rawRelationship<_M extends Model | Model[]>(relationship: string): undefined | ModelIdentifier | ModelIdentifier[] {
    return this.data.relationships?.[relationship]?.data;
  }

  /**
   * Generate a function which returns the value of the given attribute.
   *
   * @param transform A function to transform the attribute value
   */
  static attribute<T>(name: string): () => T;
  static attribute<T, O = unknown>(name: string, transform: (attr: O) => T): () => T;
  static attribute<T, O = unknown>(name: string, transform?: (attr: O) => T): () => T {
    return function (this: Model) {
      if (transform) {
        return transform(this.attribute(name));
      }

      return this.attribute(name);
    };
  }

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
  static hasOne<M extends Model>(name: string): () => M | false {
    return function (this: Model) {
      const relationshipData = this.data.relationships?.[name]?.data;

      if (relationshipData && relationshipData instanceof Array) {
        throw new Error(`Relationship ${name} on model ${this.data.type} is plural, so the hasOne method cannot be used to access it.`);
      }

      if (relationshipData) {
        return this.store.getById<M>(relationshipData.type, relationshipData.id) as M;
      }

      return false;
    };
  }

  /**
   * Generate a function which returns the value of the given has-many
   * relationship.
   *
   * @return false if no information about the relationship
   *     exists; an array if it does, containing models if they have been
   *     loaded, and undefined for those that have not.
   */
  static hasMany<M extends Model>(name: string): () => (M | undefined)[] | false {
    return function (this: Model) {
      const relationshipData = this.data.relationships?.[name]?.data;

      if (relationshipData && !(relationshipData instanceof Array)) {
        throw new Error(`Relationship ${name} on model ${this.data.type} is singular, so the hasMany method cannot be used to access it.`);
      }

      if (relationshipData) {
        return relationshipData.map((data) => this.store.getById<M>(data.type, data.id));
      }

      return false;
    };
  }

  /**
   * Transform the given value into a Date object.
   */
  static transformDate(value: string): Date;
  static transformDate(value: string | null): Date | null;
  static transformDate(value: string | undefined): Date | undefined;
  static transformDate(value: string | null | undefined): Date | null | undefined;
  static transformDate(value: string | null | undefined): Date | null | undefined {
    return value != null ? new Date(value) : value;
  }

  /**
   * Get a resource identifier object for the given model.
   */
  protected static getIdentifier(model: Model): ModelIdentifier;
  protected static getIdentifier(model?: Model): ModelIdentifier | null {
    if (!model || !('id' in model.data)) return null;

    return {
      type: model.data.type,
      id: model.data.id,
    };
  }
}
