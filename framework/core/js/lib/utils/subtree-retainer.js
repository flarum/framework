/**
  // constructor
  this.subtree = new SubtreeRetainer(
    () => this.props.post.freshness,
    () => this.showing
  );
  this.subtree.check(() => this.props.user.freshness);

  // view
  this.subtree.retain() || 'expensive expression'
 */
export default class SubtreeRetainer {
  constructor() {
    this.invalidate();
    this.callbacks = [].slice.call(arguments);
    this.old = {};
  }

  retain() {
    var needsRebuild = false;
    this.callbacks.forEach((callback, i) => {
      var result = callback();
      if (result !== this.old[i]) {
        this.old[i] = result;
        needsRebuild = true;
      }
    });
    return needsRebuild ? false : {subtree: 'retain'};
  }

  check() {
    this.callbacks = this.callbacks.concat([].slice.call(arguments));
  }

  invalidate() {
    this.old = {};
  }
}
