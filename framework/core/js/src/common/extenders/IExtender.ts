import Application from "../Application";
import type * as module from "module";

export interface IExtensionModule {
  name: string;
  exports: unknown;
}

export default interface IExtender {
  extend(app: Application, extension: IExtensionModule): void;
}
