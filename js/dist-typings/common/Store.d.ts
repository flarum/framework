/**
 * The `Store` class defines a local data store, and provides methods to
 * retrieve data from the API.
 */
export default class Store {
    constructor(models: any);
    /**
     * The local data store. A tree of resource types to IDs, such that
     * accessing data[type][id] will return the model for that type/ID.
     *
     * @type {Object}
     * @protected
     */
    protected data: Object;
    /**
     * The model registry. A map of resource types to the model class that
     * should be used to represent resources of that type.
     *
     * @type {Object}
     * @public
     */
    public models: Object;
    /**
     * Push resources contained within an API payload into the store.
     *
     * @param {Object} payload
     * @return {Model|Model[]} The model(s) representing the resource(s) contained
     *     within the 'data' key of the payload.
     * @public
     */
    public pushPayload(payload: Object): any | any[];
    /**
     * Create a model to represent a resource object (or update an existing one),
     * and push it into the store.
     *
     * @param {Object} data The resource object
     * @return {Model|null} The model, or null if no model class has been
     *     registered for this resource type.
     * @public
     */
    public pushObject(data: Object): any | null;
    /**
     * Make a request to the API to find record(s) of a specific type.
     *
     * @param {String} type The resource type.
     * @param {Integer|Integer[]|Object} [id] The ID(s) of the model(s) to retrieve.
     *     Alternatively, if an object is passed, it will be handled as the
     *     `query` parameter.
     * @param {Object} [query]
     * @param {Object} [options]
     * @return {Promise}
     * @public
     */
    public find(type: string, id?: any | any[] | Object, query?: Object | undefined, options?: Object | undefined): Promise<any>;
    /**
     * Get a record from the store by ID.
     *
     * @param {String} type The resource type.
     * @param {Integer} id The resource ID.
     * @return {Model}
     * @public
     */
    public getById(type: string, id: any): any;
    /**
     * Get a record from the store by the value of a model attribute.
     *
     * @param {String} type The resource type.
     * @param {String} key The name of the method on the model.
     * @param {*} value The value of the model attribute.
     * @return {Model}
     * @public
     */
    public getBy(type: string, key: string, value: any): any;
    /**
     * Get all loaded records of a specific type.
     *
     * @param {String} type
     * @return {Model[]}
     * @public
     */
    public all(type: string): any[];
    /**
     * Remove the given model from the store.
     *
     * @param {Model} model
     */
    remove(model: any): void;
    /**
     * Create a new record of the given type.
     *
     * @param {String} type The resource type
     * @param {Object} [data] Any data to initialize the model with
     * @return {Model}
     * @public
     */
    public createRecord(type: string, data?: Object | undefined): any;
}
