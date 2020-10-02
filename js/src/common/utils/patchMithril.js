import withAttr from './withAttr';
import Stream from './Stream';

let deprecatedMPropWarned = false;
let deprecatedMWithAttrWarned = false;

export default function patchMithril(global) {
  const defaultMithril = global.m;

  const modifiedMithril = function (comp, ...args) {
    const node = defaultMithril.apply(this, arguments);

    if (!node.attrs) node.attrs = {};

    // Allows the use of the bidi attr.
    if (node.attrs.bidi) {
      modifiedMithril.bidi(node, node.attrs.bidi);
    }

    return node;
  };

  Object.keys(defaultMithril).forEach((key) => (modifiedMithril[key] = defaultMithril[key]));

  // BEGIN DEPRECATED MITHRIL 2 BC LAYER
  modifiedMithril.prop = function (...args) {
    if (!deprecatedMPropWarned) {
      deprecatedMPropWarned = true;
      console.warn('m.prop() is deprecated, please use the Stream util (flarum/utils/Streams) instead.');
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
