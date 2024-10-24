import type Application from '../Application';
import IExtender, { IExtensionModule } from './IExtender';
export default class ThemeMode implements IExtender {
    private readonly colorSchemes;
    add(mode: string, label: string): this;
    extend(app: Application, extension: IExtensionModule): void;
}
