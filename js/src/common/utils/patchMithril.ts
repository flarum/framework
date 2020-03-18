import m from 'mithril';
import prop from 'mithril/stream';

import Component from '../Component';

export default () => {
    const mo = window['m'];

    const _m = function(comp, ...args) {
        if (!arguments[1]) arguments[1] = {};

        if (comp.prototype && comp.prototype instanceof Component) {
            // allow writing to children attribute
            Object.defineProperty(arguments[1], 'children', {
                writable: true,
            });
        }

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

    Object.keys(mo).forEach(key => (_m[key] = mo[key]));

    _m.withAttr = (key: string, cb: Function) =>
        function() {
            cb(this.getAttribute(key) || this[key]);
        };

    _m.prop = prop;

    window['m'] = _m;
};
