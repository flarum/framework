import Application from '../Application';
export interface IExtensionModule {
    name: string;
    exports: unknown;
}
export default interface IExtender {
    extend(app: Application, extension: IExtensionModule): void;
}
