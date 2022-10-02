import Application from '../Application';

export interface ExtensionModuleInterface {
  name: string;
  exports: unknown;
}

export default interface ExtenderInterface {
  extend(app: Application, extension: ExtensionModuleInterface): void;
}
