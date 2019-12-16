import prop from 'mithril/stream';

export default () => {
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

    Object.keys(mo).forEach(key => m[key] = mo[key]);

    m.withAttr = (key: string, cb: Function) => function () {
        cb(this.getAttribute(key) || this[key]);
    };

    m.prop = prop;

    global.m = m;
}
