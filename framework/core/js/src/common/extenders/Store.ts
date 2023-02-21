import Application from '../Application';
import IExtender, { IExtensionModule } from './IExtender';
import Model from '../Model';

export default class Store implements IExtender {
  private readonly models: { [type: string]: { new (): Model } } = {};

  public add(type: string, model: { new (): Model }): Store {
    this.models[type] = model;

    return this;
  }

  extend(app: Application, extension: IExtensionModule): void {
    for (const type in this.models) {
      if (app.store.models[type]) {
        throw new Error(`The model type "${type}" has already been registered with the class "${app.store.models[type].name}".`);
      }

      app.store.models[type] = this.models[type];
    }
  }
}
