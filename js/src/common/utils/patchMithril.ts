import prop from 'mithril/stream';

export default () => {
  m.withAttr = (key: string, cb: Function) => function () {
    cb(this.getAttribute(key) || this[key]);
  };

  m.prop = prop;
}
