import Stream from 'mithril/stream';
import extract from './extract';
import withAttr from './withAttr';

let deprecatedMPropWarned = false;
let deprecatedMWithAttrWarned = false;

export default function patchMithril(global) {
  const defaultMithril = global.m;

  /**
   * If the href URL of the link is the same as the current page path
   * we will not add a new entry to the browser history.
   *
   * This allows us to still refresh the Page component
   * without adding endless history entries.
   *
   * We also add the `force` attribute that adds a custom state key
   * for when you want to force a complete refresh of the Page
   */
  const defaultLinkView = defaultMithril.route.Link.view;
  const modifiedLink = {
    view: function (vnode) {
      let { href, options = {} } = vnode.attrs;

      if (href === m.route.get()) {
        if (!('replace' in options)) options.replace = true;
      }

      if (extract(vnode.attrs, 'force')) {
        if (!('state' in options)) options.state = {};
        if (!('key' in options.state)) options.state.key = Date.now();
      }

      vnode.attrs.options = options;

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

      // For some reason, m.route.Link does not like vnode.text, so if present, we
      // need to convert it to text vnodes and store it in children.
      if (node.text) {
        node.children = { tag: '#', children: node.text };
      }

      delete node.attrs.route;
    }

    return node;
  };

  Object.keys(defaultMithril).forEach((key) => (modifiedMithril[key] = defaultMithril[key]));

  modifiedMithril.stream = Stream;

  modifiedMithril.route.Link = modifiedLink;

  // BEGIN DEPRECATED MITHRIL 2 BC LAYER
  modifiedMithril.prop = function (...args) {
    if (!deprecatedMPropWarned) {
      deprecatedMPropWarned = true;
      console.warn('m.prop() is deprecated, please use m.stream() instead.');
    }
    return Stream.bind(this)(...args);
  };

  modifiedMithril.withAttr = function (...args) {
    if (!deprecatedMWithAttrWarned) {
      deprecatedMWithAttrWarned = true;
      console.warn("m.withAttr() is deprecated, please use flarum's withAttr util (flarum/utils/withAttr) instead.");
    }
    return withAttr.bind(this)(...args);
  };
  // END DEPRECATED MITHRIL 2 BC LAYER

  global.m = modifiedMithril;
}
