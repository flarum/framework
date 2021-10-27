import app from '../../admin/app';
import DefaultResolver from '../../common/resolvers/DefaultResolver';

/**
 * A custom route resolver for ExtensionPage that generates handles routes
 * to default extension pages or a page provided by an extension.
 */
export default class ExtensionPageResolver<Attrs = {}, State = {}> extends DefaultResolver<Attrs, State> {
  static extension: string | null = null;

  onmatch(args: Attrs, requestedPath: string, route: string) {
    const extensionPage = app.extensionData.getPage(args.id);

    if (extensionPage) {
      return extensionPage;
    }

    return super.onmatch(args, requestedPath, route);
  }
}
