/**
 * The `Model` class represents a local data resource. It provides methods to
 * persist changes via the API.
 *
 * @abstract
 */
export default class Model {
    /**
     * Generate a function which returns the value of the given attribute.
     *
     * @param {String} name
     * @param {function} [transform] A function to transform the attribute value
     * @return {*}
     * @public
     */
    public static attribute(name: string, transform?: Function | undefined): any;
    /**
     * Generate a function which returns the value of the given has-one
     * relationship.
     *
     * @param {String} name
     * @return {Model|Boolean|undefined} false if no information about the
     *     relationship exists; undefined if the relationship exists but the model
     *     has not been loaded; or the model if it has been loaded.
     * @public
     */
    public static hasOne(name: string): Model | boolean | undefined;
    /**
     * Generate a function which returns the value of the given has-many
     * relationship.
     *
     * @param {String} name
     * @return {Array|Boolean} false if no information about the relationship
     *     exists; an array if it does, containing models if they have been
     *     loaded, and undefined for those that have not.
     * @public
     */
    public static hasMany(name: string): any[] | boolean;
    /**
     * Transform the given value into a Date object.
     *
     * @param {String} value
     * @return {Date|null}
     * @public
     */
    public static transformDate(value: string): Date | null;
    /**
     * Get a resource identifier object for the given model.
     *
     * @param {Model} model
     * @return {Object}
     * @protected
     */
    protected static getIdentifier(model: Model): Object;
    /**
     * @param {Object} data A resource object from the API.
     * @param {Store} store The data store that this model should be persisted to.
     * @public
     */
    constructor(data?: Object, store?: any);
    /**
     * The resource object from the API.
     *
     * @type {Object}
     * @public
     */
    public data: Object;
    /**
     * The time at which the model's data was last updated. Watching the value
     * of this property is a fast way to retain/cache a subtree if data hasn't
     * changed.
     *
     * @type {Date}
     * @public
     */
    public freshness: Date;
    /**
     * Whether or not the resource exists on the server.
     *
     * @type {Boolean}
     * @public
     */
    public exists: boolean;
    /**
     * The data store that this resource should be persisted to.
     *
     * @type {Store}
     * @protected
     */
    protected store: any;
    /**
     * Get the model's ID.
     *
     * @return {Integer}
     * @public
     * @final
     */
    public id(): any;
    /**
     * Get one of the model's attributes.
     *
     * @param {String} attribute
     * @return {*}
     * @public
     * @final
     */
    public attribute(attribute: string): any;
    /**
     * Merge new data into this model locally.
     *
     * @param {Object} data A resource object to merge into this model
     * @public
     */
    public pushData(data: Object): void;
    /**
     * Merge new attributes into this model locally.
     *
     * @param {Object} attributes The attributes to merge.
     * @public
     */
    public pushAttributes(attributes: Object): void;
    /**
     * Merge new attributes into this model, both locally and with persistence.
     *
     * @param {Object} attributes The attributes to save. If a 'relationships' key
     *     exists, it will be extracted and relationships will also be saved.
     * @param {Object} [options]
     * @return {Promise}
     * @public
     */
    public save(attributes: Object, options?: Object | undefined): Promise<any>;
    /**
     * Send a request to delete the resource.
     *
     * @param {Object} body Data to send along with the DELETE request.
     * @param {Object} [options]
     * @return {Promise}
     * @public
     */
    public delete(body: Object, options?: Object | undefined): Promise<any>;
    /**
     * Construct a path to the API endpoint for this resource.
     *
     * @return {String}
     * @protected
     */
    protected apiEndpoint(): string;
    copyData(): any;
}
