import prop from 'mithril/stream';

export default () => {
  m.withAttr = (key: string, cb: Function) => function () {
    cb(this.getAttribute(key));
  };

  m.prop = prop;
}
