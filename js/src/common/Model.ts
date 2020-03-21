import Store from './Store';

export interface Identifier {
    type: string;
    id: string;
}

export interface Data extends Identifier {
    attributes?: { [key: string]: any };
    relationships?: { [key: string]: { data: Identifier | Identifier[] } };
}

/**
 * The `Model` class represents a local data resource. It provides methods to
 * persist changes via the API.
 */
export default abstract class Model {
    /**
     * The resource object from the API.
     */
    data: Data;

    payload: any;

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
    protected store?: Store;

    /**
     * @param data A resource object from the API.
     * @param store The data store that this model should be persisted to.
     */
    constructor(data = <Data>{}, store?: Store) {
        this.data = data;
        this.store = store;

        this.freshness = new Date();
        this.exists = false;
    }

    /**
     * Get the model's ID.
     * @final
     */
    id(): string {
        return this.data.id;
    }

    /**
     * Get one of the model's attributes.
     * @final
     */
    attribute(attribute: string): any {
        return this.data.attributes && this.data.attributes[attribute];
    }

    /**
     * Merge new data into this model locally.
     *
     * @param data A resource object to merge into this model
     */
    public pushData(data: {}) {
        // Since most of the top-level items in a resource object are objects
        // (e.g. relationships, attributes), we'll need to check and perform the
        // merge at the second level if that's the case.
        for (const key in data) {
            if (typeof data[key] === 'object') {
                this.data[key] = this.data[key] || {};

                // For every item in a second-level object, we want to check if we've
                // been handed a Model instance. If so, we will convert it to a
                // relationship data object.
                for (const innerKey in data[key]) {
                    if (data[key][innerKey] instanceof Model) {
                        data[key][innerKey] = { data: Model.getIdentifier(data[key][innerKey]) };
                    }
                    this.data[key][innerKey] = data[key][innerKey];
                }
            } else {
                this.data[key] = data[key];
            }
        }

        // Now that we've updated the data, we can say that the model is fresh.
        // This is an easy way to invalidate retained subtrees etc.
        this.freshness = new Date();
    }

    /**
     * Merge new attributes into this model locally.
     *
     * @param attributes The attributes to merge.
     */
    pushAttributes(attributes: any) {
        this.pushData({ attributes });
    }

    /**
     * Merge new attributes into this model, both locally and with persistence.
     *
     * @param attributes The attributes to save. If a 'relationships' key
     *     exists, it will be extracted and relationships will also be saved.
     * @param [options]
     */
    save(attributes: any, options: any = {}): Promise<Model | Model[]> {
        const data: Data = {
            type: this.data.type,
            id: this.data.id,
            attributes,
        };

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

        const request = { data };
        if (options.meta) request.meta = options.meta;

        return app
            .request(
                Object.assign(
                    {
                        method: this.exists ? 'PATCH' : 'POST',
                        url: app.forum.attribute('apiUrl') + this.apiEndpoint(),
                        body: request,
                    },
                    options
                )
            )
            .then(
                // If everything went well, we'll make sure the store knows that this
                // model exists now (if it didn't already), and we'll push the data that
                // the API returned into the store.
                payload => {
                    this.store.data[payload.data.type] = this.store.data[payload.data.type] || {};
                    this.store.data[payload.data.type][payload.data.id] = this;
                    return this.store.pushPayload(payload);
                },

                // If something went wrong, though... good thing we backed up our model's
                // old data! We'll revert to that and let others handle the error.
                response => {
                    this.pushData(oldData);
                    m.redraw();
                    throw response;
                }
            );
    }

    /**
     * Send a request to delete the resource.
     *
     * @param {Object} body Data to send along with the DELETE request.
     * @param {Object} [options]
     * @return {Promise}
     * @public
     */
    delete(body = {}, options = {}) {
        if (!this.exists) return Promise.resolve();

        return app
            .request(
                Object.assign(
                    {
                        method: 'DELETE',
                        url: app.forum.attribute('apiUrl') + this.apiEndpoint(),
                        body,
                    },
                    options
                )
            )
            .then(() => {
                this.exists = false;
                this.store!.remove(this);
            });
    }

    /**
     * Construct a path to the API endpoint for this resource.
     *
     * @return {String}
     * @protected
     */
    apiEndpoint() {
        return '/' + this.data.type + (this.exists ? '/' + this.data.id : '');
    }

    copyData() {
        return JSON.parse(JSON.stringify(this.data));
    }

    /**
     * Generate a function which returns the value of the given attribute.
     *
     * @param name
     * @param [transform] A function to transform the attribute value
     */
    static attribute(name: string, transform?: Function): () => any {
        return function(this: Model) {
            const value = this.data.attributes && this.data.attributes[name];

            return transform ? transform(value) : value;
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
    static hasOne(name: string): () => Model | boolean {
        return function(this: Model) {
            if (this.data.relationships) {
                const relationship = this.data.relationships[name];

                if (relationship && !Array.isArray(relationship.data)) {
                    return app.store.getById(relationship.data.type, relationship.data.id);
                }
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
    static hasMany(name: string): () => any[] | false {
        return function(this: Model) {
            if (this.data.relationships) {
                const relationship = this.data.relationships[name];

                if (relationship && Array.isArray(relationship.data)) {
                    return relationship.data.map(data => app.store.getById(data.type, data.id));
                }
            }

            return false;
        };
    }

    /**
     * Transform the given value into a Date object.
     */
    static transformDate(value: string): Date | null {
        return value ? new Date(value) : null;
    }

    /**
     * Get a resource identifier object for the given model.
     */
    protected static getIdentifier(model: Model): Identifier {
        return {
            type: model.data.type,
            id: model.data.id,
        };
    }
}
