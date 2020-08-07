import Stream from 'mithril/stream';

export default function patchMithril(global) {
  const defaultMithril = global.m;

  const modifiedMithril = function (comp, ...args) {
    const node = defaultMithril.apply(this, arguments);

    if (!node.attrs) node.attrs = {};

    if (node.attrs.bidi) {
      modifiedMithril.bidi(node, node.attrs.bidi);
    }

    if (node.attrs.route) {
      node.attrs.href = node.attrs.route;
      node.attrs.tag = modifiedMithril.route.Link;

      delete node.attrs.route;
    }

    return node;
  };

  Object.keys(defaultMithril).forEach((key) => (modifiedMithril[key] = defaultMithril[key]));

  modifiedMithril.stream = Stream;

  global.m = modifiedMithril;
}
