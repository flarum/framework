import app from '../../admin/app';
import DefaultResolver from '../../common/resolvers/DefaultResolver';
import ExtensionPage, { ExtensionPageAttrs } from '../components/ExtensionPage';

/**
 * A custom route resolver for ExtensionPage that generates handles routes
 * to default extension pages or a page provided by an extension.
 */
export default class ExtensionPageResolver<
  Attrs extends ExtensionPageAttrs = ExtensionPageAttrs,
  RouteArgs extends Record<string, unknown> = {}
> extends DefaultResolver<Attrs, ExtensionPage<Attrs>, RouteArgs> {
  static extension: string | null = null;

  onmatch(args: Attrs & RouteArgs, requestedPath: string, route: string) {
    const extensionPage = app.extensionData.getPage<Attrs>(args.id);

    if (extensionPage) {
      return extensionPage;
    }

    return super.onmatch(args, requestedPath, route);
  }
}
