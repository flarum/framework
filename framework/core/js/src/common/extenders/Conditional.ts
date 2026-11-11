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
   * Apply extenders only if all of the given extensions are enabled.
   *
   * @param extensionIds The IDs of the extensions.
   * @param extenders A callback returning an array of extenders.
   *
   * @example
   * new Extend.Conditional()
   *   .whenExtensionsEnabled(['flarum-tags', 'flarum-mentions'], () => [
   *     new Extend.Model(Discussion).attribute<boolean>('acmeMentionsTag'),
   *   ])
   */
  public whenExtensionsEnabled(extensionIds: string[], extenders: () => IExtender<any>[]): this {
    return this.when(() => extensionIds.every((extensionId) => extensionId in flarum.extensions), extenders);
  }

  /**
   * Apply extenders only if all of the given extensions are disabled.
   *
   * @param extensionIds The IDs of the extensions.
   * @param extenders A callback returning an array of extenders.
   *
   * @example
   * new Extend.Conditional()
   *   .whenExtensionsDisabled(['flarum-tags', 'flarum-mentions'], () => [
   *     new Extend.Model(Discussion).attribute<string>('acmeCategoryLabel'),
   *   ])
   */
  public whenExtensionsDisabled(extensionIds: string[], extenders: () => IExtender<any>[]): this {
    return this.when(() => extensionIds.every((extensionId) => !(extensionId in flarum.extensions)), extenders);
  }

  /**
   * Apply extenders only if a forum attribute matches an expected value.
   *
   * Uses strict comparison (===). The value is read from the forum payload,
   * as `app.forum` doesn't exist yet when extenders are applied.
   *
   * @param key The name of the forum attribute.
   * @param expected The expected value.
   * @param extenders A callback returning an array of extenders.
   *
   * @example
   * new Extend.Conditional()
   *   .whenForumAttribute('acmeAdvancedMode', true, () => [
   *     new Extend.Routes().add('acme.advanced', '/acme/advanced', AdvancedPage),
   *   ])
   */
  public whenForumAttribute(key: string, expected: unknown, extenders: () => IExtender<any>[]): this {
    return this.when((app) => {
      const forum = app.data.resources.find((resource) => resource.type === 'forums');

      return forum?.attributes?.[key] === expected;
    }, extenders);
  }

  /**
   * Apply extenders based on a condition.
   *
   * Conditions are checked before the application boots, so `app.forum` and
   * `app.session` aren't available yet. Use `whenForumAttribute()` to check
   * a forum attribute.
   *
   * @param condition A boolean, or a callback returning a boolean.
   * @param extenders A callback returning an array of extenders.
   *
   * @example
   * new Extend.Conditional()
   *   .when(
   *     () => 'flarum-tags' in flarum.extensions || 'flarum-mentions' in flarum.extensions,
   *     () => [new Extend.Model(Discussion).attribute<boolean>('acmeIsLinked')]
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
