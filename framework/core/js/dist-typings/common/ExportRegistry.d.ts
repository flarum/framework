/**
 * @internal
 */
export interface IExportRegistry {
    moduleExports: Map<string, Map<string, any>>;
    onLoads: Map<string, Map<string, Function[]>>;
    /**
     * Add an instance to the registry.
     */
    add(namespace: string, id: string, object: any): void;
    /**
     * Add a function to run when object of id "id" is added (or overriden).
     * If such an object is already registered, the handler will be applied immediately.
     */
    onLoad(namespace: string, id: string, handler: Function): void;
    /**
     * Retrieve an object of type `id` from the registry.
     */
    get(namespace: string, id: string): any;
}
export default class ExportRegistry implements IExportRegistry {
    moduleExports: Map<string, Map<string, any>>;
    onLoads: Map<string, Map<string, Function[]>>;
    add(namespace: string, id: string, object: any): void;
    onLoad(namespace: string, id: string, handler: Function): void;
    get(namespace: string, id: string): any;
}
