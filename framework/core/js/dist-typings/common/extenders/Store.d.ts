import Application from '../Application';
import IExtender, { IExtensionModule } from './IExtender';
import Model from '../Model';
export default class Store implements IExtender {
    private readonly models;
    add(type: string, model: {
        new (): Model;
    }): Store;
    extend(app: Application, extension: IExtensionModule): void;
}
