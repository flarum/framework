export default class Pane {
  constructor(element) {
    this.pinnedKey = 'panePinned';

    this.$element = $(element);

    this.pinned = localStorage.getItem(this.pinnedKey) === 'true';
    this.active = false;
    this.showing = false;
    this.render();
  }

  enable() {
    this.active = true;
    this.render();
  }

  disable() {
    this.active = false;
    this.showing = false;
    this.render();
  }

  show() {
    clearTimeout(this.hideTimeout);
    this.showing = true;
    this.render();
  }

  hide() {
    this.showing = false;
    this.render();
  }

  onmouseleave() {
    this.hideTimeout = setTimeout(this.hide.bind(this), 250);
  }

  togglePinned() {
    localStorage.setItem(this.pinnedKey, (this.pinned = !this.pinned) ? 'true' : 'false');
    this.render();
  }

  render() {
    this.$element
      .toggleClass('pane-pinned', this.pinned)
      .toggleClass('has-pane', this.active)
      .toggleClass('pane-showing', this.showing);
  }
}
