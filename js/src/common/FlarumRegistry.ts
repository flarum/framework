interface ExportRegistry {
  moduleExports: object;

  onLoads: object;

  /**
   * Add an instance to the registry.
   * This serves as the equivalent of `flarum.core.compat[id] = object`
   */
  add(id: string, object: any);

  /**
   * Add a function to run when object of id "id" is added (or overriden).
   * If such an object is already registered, the handler will be applied immediately.
   */
  onLoad(id: string, handler: Function);

  /**
   * Retrieve an object of type `id` from the registry.
   */
  get(id: string): any;
}

export default class FlarumRegistry implements ExportRegistry {
  moduleExports = {};

  onLoads = {};

  add(id: string, object: any) {
    const onLoads = this.onLoads[id];
    if (onLoads) {
      onLoads.map((onLoad) => {
        object = onLoad(object);
      });
    }

    this.moduleExports[id] = object;
  }

  onLoad(id: string, handler: Function) {
    const loadedObject = this.moduleExports[id];
    if (loadedObject) {
      this.moduleExports[id] = handler(loadedObject);
    } else {
      this.onLoads[id] = this.onLoads[id] || [];
      this.onLoads[id].push(handler);
    }
  }

  get(id: string): any {
    return this.moduleExports[id];
  }
}
