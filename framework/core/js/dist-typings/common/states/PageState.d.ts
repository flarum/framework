export default class PageState {
    type: Function | null;
    data: {
        routeName?: string | null;
    } & Record<string, any>;
    constructor(type: Function | null, data?: any);
    /**
     * Determine whether the page matches the given class and data.
     *
     * @param {object} type The page class to check against. Subclasses are accepted as well.
     * @param {Record<string, unknown>} data
     * @return {boolean}
     */
    matches(type: Function, data?: any): boolean;
    get(key: string): any;
    set(key: string, value: any): void;
}
