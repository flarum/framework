import app from '../../admin/app';
import DefaultResolver from '../../common/resolvers/DefaultResolver';
import ExtensionPage, { ExtensionPageAttrs } from '../components/ExtensionPage';

/**
 * A custom route resolver for ExtensionPage that generates handles routes
 * to default extension pages or a page provided by an extension.
 */
export default class ExtensionPageResolver<
  Attrs extends ExtensionPageAttrs = ExtensionPageAttrs,
  RouteArgs extends Record<string, unknown> = {},
> extends DefaultResolver<Attrs, ExtensionPage<Attrs>, RouteArgs> {
  static extension: string | null = null;

  /**
   * Switching tabs (`?tab=`) keeps the page, and any unsaved changes on it.
   */
  makeKey(): string {
    const { tab, ...params } = m.route.param();

    return this.routeName + JSON.stringify(params);
  }

  onmatch(args: Attrs & RouteArgs, requestedPath: string, route: string) {
    const extensionPage = app.registry.getPage<Attrs>(args.id);

    if (extensionPage) {
      return Promise.resolve(extensionPage);
    }

    return super.onmatch(args, requestedPath, route);
  }
}
