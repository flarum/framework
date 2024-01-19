import Application from '../Application';

export interface IExtensionModule {
  name: string;
  exports: unknown;
}

export default interface IExtender<App = Application> {
  extend(app: App, extension: IExtensionModule): void;
}
