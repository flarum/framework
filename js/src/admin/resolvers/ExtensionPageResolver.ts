import DefaultResolver from '../../common/resolvers/DefaultResolver';
import ExtensionPage from '../components/ExtensionPage';

/**
 * A custom route resolver for ExtensionPage that generates handles routes
 * to default extension pages or a page provided by an extension.
 */
export default class ExtensionPageResolver extends DefaultResolver {
  static extension: string | null = null;

  onmatch(args, requestedPath, route) {
    const extensionRoute = app.routes[args.id];

    if (extensionRoute) {
      return extensionRoute.component;
    }

    return super.onmatch(args, requestedPath, route);
  }
}
