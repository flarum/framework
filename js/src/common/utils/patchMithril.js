import Stream from 'mithril/stream';

export default function patchMithril(global) {
  const defaultMithril = global.m;

  /**
   * Due to mithril 2.0, setting a route will not re-call oninit if the component handling the route has not changed.
   * We, however, want to keep this behavior since it's more intuitive to users (clicking home should refresh home).
   * Mithril allows us to provide an options parameter to m.route.set, which, if has the state.key parameter changed,
   * will force a re-oninit. See https://mithril.js.org/route.html#key-parameter.
   *
   * However, manually implementing this on every button and component is both tedious, and will make further changes in
   * functionality difficult to implement at scale, so we patch it here. The original behavior can be replicated by passing
   * an empty object as the "options" attr to a link component.
   *
   * Please note that any code that manually calls m.route.set will need to provide something like this to the
   * options parameter itself. Patching m.route.set would be more convenient, but would too severely restrict flexibility.
   */
  const defaultLinkView = defaultMithril.route.Link.view;
  const modifiedLink = {
    view: function (vnode) {
      if (!vnode.attrs.options) vnode.attrs.options = { state: { key: Date.now() } };

      return defaultLinkView(vnode);
    },
  };

  const modifiedMithril = function (comp, ...args) {
    const node = defaultMithril.apply(this, arguments);

    if (!node.attrs) node.attrs = {};

    // Allows the use of the bidi attr.
    if (node.attrs.bidi) {
      modifiedMithril.bidi(node, node.attrs.bidi);
    }

    // Allows us to use a "route" attr on links, which will automatically convert the link to one which
    // supports linking to other pages in the SPA without refreshing the document.
    if (node.attrs.route) {
      node.attrs.href = node.attrs.route;
      node.tag = modifiedLink;

      delete node.attrs.route;
    }

    return node;
  };

  Object.keys(defaultMithril).forEach((key) => (modifiedMithril[key] = defaultMithril[key]));

  modifiedMithril.route.Link = modifiedLink;

  modifiedMithril.stream = Stream;

  global.m = modifiedMithril;
}
