import DefaultResolver from '../../common/resolvers/DefaultResolver';

/**
 * A custom route resolver for ExtensionPage that generates handles routes
 * to default extension pages or a page provided by an extension.
 */
export default class ExtensionPageResolver extends DefaultResolver {
  static extension: string | null = null;

  onmatch(args, requestedPath, route) {
    const extensionPage = app.extensionData.getPage(args.id);

    if (extensionPage) {
      return extensionPage;
    }

    return super.onmatch(args, requestedPath, route);
  }
}
