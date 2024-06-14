import Application from '../Application';
import IExtender, { IExtensionModule } from './IExtender';
import ThemeModeComponent from '../components/ThemeMode';

export default class ThemeMode implements IExtender {
  private readonly colorSchemes: string[] = [];

  public add(mode: string): this {
    this.colorSchemes.push(mode);

    return this;
  }

  extend(app: Application, extension: IExtensionModule): void {
    ThemeModeComponent.colorSchemes = Array.from(new Set([...ThemeModeComponent.colorSchemes, ...this.colorSchemes]));
  }
}
