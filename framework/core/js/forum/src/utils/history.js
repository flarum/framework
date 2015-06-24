export default class History {
  constructor() {
    this.stack = [];
    this.push('index', '/');
  }

  top() {
    return this.stack[this.stack.length - 1];
  }

  push(name, url) {
    var url = url || m.route();

    // maybe? prevents browser back button from breaking history
    var secondTop = this.stack[this.stack.length - 2];
    if (secondTop && secondTop.name === name) {
      this.stack.pop();
    }

    var top = this.top();
    if (top && top.name === name) {
      top.url = url;
    } else {
      this.stack.push({name: name, url: url});
    }
  }

  canGoBack() {
    return this.stack.length > 1;
  }

  back() {
    this.stack.pop();
    var top = this.top();
    m.route(top.url);
  }

  home() {
    this.stack.splice(1);
    m.route('/');
  }
}
