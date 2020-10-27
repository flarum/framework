import DefaultResolver from '../../common/resolvers/DefaultResolver';
import ExtensionPage from '../components/ExtensionPage';

/**
 * A custom route resolver for DiscussionPage that generates the same key to all posts
 * on the same discussion. It triggers a scroll when going from one post to another
 * in the same discussion.
 */
export default class ExtensionPageResolver extends DefaultResolver {
  static extension: string | null = null;

  onmatch(args, requestedPath, route) {
    const extensionRoute = app.routes[args.id];

    if (extensionRoute) {
      this.component = extensionRoute.component;
    }

    return super.onmatch(args, requestedPath, route);
  }
}
