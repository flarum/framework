import Application from '../Application';
import IExtender, { IExtensionModule } from './IExtender';
import ThemeModeComponent, { type ColorSchemeData } from '../components/ThemeMode';

export default class ThemeMode implements IExtender {
  private readonly colorSchemes: ColorSchemeData[] = [];

  public add(mode: string, label: string): this {
    this.colorSchemes.push({
      id: mode,
      label,
    });

    return this;
  }

  extend(app: Application, extension: IExtensionModule): void {
    ThemeModeComponent.colorSchemes = Array.from(new Set([...ThemeModeComponent.colorSchemes, ...this.colorSchemes]));
  }
}
