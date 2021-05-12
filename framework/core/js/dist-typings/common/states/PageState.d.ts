export default class PageState {
    constructor(type: any, data?: {});
    type: any;
    data: {};
    /**
     * Determine whether the page matches the given class and data.
     *
     * @param {object} type The page class to check against. Subclasses are
     *                      accepted as well.
     * @param {object} data
     * @return {boolean}
     */
    matches(type: object, data?: object): boolean;
    get(key: any): any;
    set(key: any, value: any): void;
}
