import Component from '../Component';

export default function patchMithril(global) {
  const mo = global.m;

  const m = function (comp, ...args) {
    const node = mo.apply(this, arguments);

    if (!node.attrs) node.attrs = {};

    if (node.attrs.bidi) {
      m.bidi(node, node.attrs.bidi);
    }

    if (node.attrs.route) {
      node.attrs.href = node.attrs.route;
      node.attrs.tag = m.route.Link;

      delete node.attrs.route;
    }

    return node;
  };

  Object.keys(mo).forEach((key) => (m[key] = mo[key]));

  global.m = m;
}
