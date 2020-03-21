import Model from './Model';

/**
 * The `Store` class defines a local data store, and provides methods to
 * retrieve data from the API.
 */
export default class Store {
    /**
     * The local data store. A tree of resource types to IDs, such that
     * accessing data[type][id] will return the model for that type/ID.
     */
    data: { [key: string]: Model[] } = {};

    /**
     * The model registry. A map of resource types to the model class that
     * should be used to represent resources of that type.
     */
    models: {};

    constructor(models) {
        this.models = models;
    }

    /**
     * Push resources contained within an API payload into the store.
     *
     * @param payload
     * @return The model(s) representing the resource(s) contained
     *     within the 'data' key of the payload.
     */
    pushPayload(payload: { included?: {}[]; data?: {} | {}[] }): Model | Model[] {
        if (payload.included) payload.included.map(this.pushObject.bind(this));

        const result: any = payload.data instanceof Array ? payload.data.map(this.pushObject.bind(this)) : this.pushObject(payload.data);

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
     * @param {Object} data The resource object
     * @return The model, or null if no model class has been
     *     registered for this resource type.
     */
    pushObject(data): Model | null {
        if (!this.models[data.type]) return null;

        const type = (this.data[data.type] = this.data[data.type] || {});

        if (type[data.id]) {
            type[data.id].pushData(data);
        } else {
            type[data.id] = this.createRecord(data.type, data);
        }

        type[data.id].exists = true;

        return type[data.id];
    }

    /**
     * Make a request to the API to find record(s) of a specific type.
     *
     * @param type The resource type.
     * @param [id] The ID(s) of the model(s) to retrieve.
     *     Alternatively, if an object is passed, it will be handled as the
     *     `query` parameter.
     * @param query
     * @param options
     */
    find<T extends Model = Model>(type: string, id?: number | number[] | any, query = {}, options = {}): Promise<T | T[]> {
        let params = query;
        let url = `${app.forum.attribute('apiUrl')}/${type}`;

        if (id instanceof Array) {
            url += `?filter[id]=${id.join(',')}`;
        } else if (typeof id === 'object') {
            params = id;
        } else if (id) {
            url += `/${id}`;
        }

        return <Promise<T | T[]>>app
            .request(
                Object.assign(
                    {
                        method: 'GET',
                        url,
                        params,
                    },
                    options
                )
            )
            .then(this.pushPayload.bind(this));
    }

    /**
     * Get a record from the store by ID.
     *
     * @param type The resource type.
     * @param id The resource ID.
     */
    getById<T extends Model = Model>(type: string, id: number | string): T {
        return this.data[type] && (this.data[type][id] as T);
    }

    /**
     * Get a record from the store by the value of a model attribute.
     *
     * @param type The resource type.
     * @param key The name of the method on the model.
     * @param value The value of the model attribute.
     */
    getBy<T extends Model = Model>(type: string, key: string, value: any): T {
        return this.all<T>(type).filter(model => model[key]() === value)[0];
    }

    /**
     * Get all loaded records of a specific type.
     */
    all<T extends Model = Model>(type: string): T[] {
        const records = this.data[type];

        return records ? Object.keys(records).map(id => records[id]) : [];
    }

    /**
     * Remove the given model from the store.
     */
    remove(model: Model) {
        delete this.data[model.data.type][model.id()];
    }

    /**
     * Create a new record of the given type.
     *
     * @param {String} type The resource type
     * @param {Object} [data] Any data to initialize the model with
     */
    createRecord<T extends Model = Model>(type: string, data: any = {}): T {
        data.type = data.type || type;

        return new this.models[type](data, this);
    }
}
