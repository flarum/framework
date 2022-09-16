import IExtender, { IExtensionModule } from './IExtender';
import Application from '../Application';
import ActualModel from '../Model';

export default class Model implements IExtender {
  private model: { new (): ActualModel };
  private type: string | null = null;

  public constructor(model: { new (): ActualModel }) {
    this.model = model;
  }

  public attribute<T, O = unknown>(name: string, transform: ((attr: O) => T) | null = null): Model {
    this.model.prototype[name] = transform ? ActualModel.attribute<T, O>(name, transform) : ActualModel.attribute<T>(name);

    return this;
  }

  public hasOne<M extends ActualModel>(name: string): Model {
    this.model.prototype[name] = ActualModel.hasOne<M>(name);

    return this;
  }

  public hasMany<M extends ActualModel>(name: string): Model {
    this.model.prototype[name] = ActualModel.hasMany<M>(name);

    return this;
  }

  /**
   * Set the model type and register it to the application.
   *
   * @param type The model type name. Must be the same as the backend serializer's type name.
   */
  public register(type: string): Model {
    this.type = type;

    return this;
  }

  extend(app: Application, extension: IExtensionModule): void {
    if (this.type) {
      app.store.models[this.type] = this.model;
    }
  }
}
