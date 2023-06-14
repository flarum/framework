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
  moduleExports = new Map<string, Map<string, any>>();
  onLoads = new Map<string, Map<string, Function[]>>();

  add(namespace: string, id: string, object: any): void {
    this.moduleExports.set(namespace, this.moduleExports.get(namespace) || new Map());
    this.moduleExports.get(namespace)?.set(id, object);

    this.onLoads
      .get(namespace)
      ?.get(id)
      ?.forEach((handler) => handler(object));
  }

  onLoad(namespace: string, id: string, handler: Function): void {
    if (this.moduleExports.has(namespace) && this.moduleExports.get(namespace)?.has(id)) {
      handler(this.moduleExports.get(namespace)?.get(id));
    } else {
      this.onLoads.set(namespace, this.onLoads.get(namespace) || new Map());
      this.onLoads.get(namespace)?.set(id, this.onLoads.get(namespace)?.get(id) || []);
      this.onLoads.get(namespace)?.get(id)?.push(handler);
    }
  }

  get(namespace: string, id: string): any {
    const module = this.moduleExports.get(namespace)?.get(id);

    if (!module) {
      console.warn(`No module found for ${namespace}:${id}`);
    }

    return module;
  }
}
