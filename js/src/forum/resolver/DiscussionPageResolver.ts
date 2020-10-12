import DefaultResolver from '../../common/resolvers/DefaultResolver';

/**
 * A custom route resolver for DiscussionPage that generates the same key to all posts
 * on the same discussion. It triggers a scroll when going from one post to another
 * in the same discussion.
 */
export default class DiscussionPageResolver extends DefaultResolver {
  static scrollToPostNumber: string | null = null;

  makeKey() {
    const params = { ...m.route.param() };
    if ('near' in params) {
      delete params.near;
    }
    if ('id' in params && params.id.includes('-')) {
      params.id = params.id.split('-')[0];
    }
    return this.routeName.replace('.near', '') + JSON.stringify(params);
  }

  onmatch(args, requestedPath, route) {
    const sameDiscussion = m.route.param('id') && args.id && m.route.param('id').split('-')[0] === args.id.split('-')[0];
    if (route.startsWith('/d/:id') && sameDiscussion) {
      DiscussionPageResolver.scrollToPostNumber = args.near;
    }

    return super.onmatch(args, requestedPath, route);
  }

  render(vnode) {
    if (DiscussionPageResolver.scrollToPostNumber !== null) {
      console.log(DiscussionPageResolver.scrollToPostNumber);
      app.current.get('stream').goToNumber(parseInt(DiscussionPageResolver.scrollToPostNumber));
      DiscussionPageResolver.scrollToPostNumber = null;
    }

    return super.render(vnode);
  }
}
