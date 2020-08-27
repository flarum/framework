/**
 * Generates a route resolver for a given component, to which it provides
 * a key as a 'routeName' attr.
 */
export default function defaultResolver(component, key) {
  return {
    onmatch(args, requestedPath, route) {
      return component;
    },

    render(vnode) {
      vnode.attrs.routeName = key;

      return vnode;
    },
  };
}
