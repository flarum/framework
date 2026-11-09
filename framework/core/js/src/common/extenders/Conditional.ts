import type Application from '../Application';
import IExtender, { IExtensionModule } from './IExtender';

export default class Conditional implements IExtender {
  protected conditions: { condition: boolean | ((app: Application) => boolean); extenders: () => IExtender<any>[] }[] = [];

  /**
   * Apply extenders only if an extension is enabled.
   *
   * Only extensions with JS for the current frontend can be detected.
   *
   * @param extensionId The ID of the extension.
   * @param extenders A callback returning an array of extenders.
   *
   * @example
   * new Extend.Conditional()
   *   .whenExtensionEnabled('flarum-tags', () => [
   *     new Extend.Model(Discussion).hasOne<Tag>('acmeFeaturedTag'),
   *   ])
   */
  public whenExtensionEnabled(extensionId: string, extenders: () => IExtender<any>[]): this {
    return this.when(() => extensionId in flarum.extensions, extenders);
  }

  /**
   * Apply extenders only if an extension is disabled.
   *
   * @param extensionId The ID of the extension.
   * @param extenders A callback returning an array of extenders.
   *
   * @example
   * new Extend.Conditional()
   *   .whenExtensionDisabled('flarum-tags', () => [
   *     new Extend.Model(Discussion).attribute<string>('acmeCategoryLabel'),
   *   ])
   */
  public whenExtensionDisabled(extensionId: string, extenders: () => IExtender<any>[]): this {
    return this.when(() => !(extensionId in flarum.extensions), extenders);
  }

  /**
   * Apply extenders based on a condition.
   *
   * @param condition A boolean, or a callback returning a boolean.
   * @param extenders A callback returning an array of extenders.
   *
   * @example
   * new Extend.Conditional()
   *   .when(
   *     (app) => !!app.forum.attribute('acmeAdvancedMode'),
   *     () => [new Extend.Routes().add('acme.advanced', '/acme/advanced', AdvancedPage)]
   *   )
   */
  public when(condition: boolean | ((app: Application) => boolean), extenders: () => IExtender<any>[]): this {
    this.conditions.push({ condition, extenders });

    return this;
  }

  extend(app: Application, extension: IExtensionModule): void {
    for (const { condition, extenders } of this.conditions) {
      if (typeof condition === 'function' ? condition(app) : condition) {
        for (const extender of extenders()) {
          extender.extend(app, extension);
        }
      }
    }
  }
}
