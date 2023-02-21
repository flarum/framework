import IExtender, { IExtensionModule } from './IExtender';
import Application from '../Application';
import ActualModel from '../Model';

export default class Model implements IExtender {
  private readonly model: { new (): ActualModel };
  private callbacks: Array<() => void> = [];

  public constructor(model: { new (): ActualModel }) {
    this.model = model;
  }

  public attribute<T, O = unknown>(name: string, transform: ((attr: O) => T) | null = null): Model {
    this.callbacks.push(() => {
      this.model.prototype[name] = transform ? ActualModel.attribute<T, O>(name, transform) : ActualModel.attribute<T>(name);
    });

    return this;
  }

  public hasOne<M extends ActualModel>(name: string): Model {
    this.callbacks.push(() => {
      this.model.prototype[name] = ActualModel.hasOne<M>(name);
    });

    return this;
  }

  public hasMany<M extends ActualModel>(name: string): Model {
    this.callbacks.push(() => {
      this.model.prototype[name] = ActualModel.hasMany<M>(name);
    });

    return this;
  }

  extend(app: Application, extension: IExtensionModule): void {
    for (const callback of this.callbacks) {
      callback.call(this);
    }
  }
}
