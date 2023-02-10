import IExtender, { IExtensionModule } from './IExtender';
import Application from '../Application';
import ActualModel from '../Model';
export default class Model implements IExtender {
    private readonly model;
    private callbacks;
    constructor(model: {
        new (): ActualModel;
    });
    attribute<T, O = unknown>(name: string, transform?: ((attr: O) => T) | null): Model;
    hasOne<M extends ActualModel>(name: string): Model;
    hasMany<M extends ActualModel>(name: string): Model;
    extend(app: Application, extension: IExtensionModule): void;
}
