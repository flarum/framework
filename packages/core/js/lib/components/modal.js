import Component from 'flarum/component';

export default class Modal extends Component {
  view() {
    return m('div.modal.fade', {config: this.onload.bind(this)}, this.component && this.component.view())
  }

  onload(element, isInitialized) {
    if (isInitialized) { return; }

    this.element(element);

    this.$()
      .on('hidden.bs.modal', this.destroy.bind(this))
      .on('shown.bs.modal', this.ready.bind(this));
  }

  show(component) {
    clearTimeout(this.hideTimeout);
    this.component = component;
    m.redraw(true);
    this.$().modal('show');
    this.ready();
  }

  close() {
    this.hideTimeout = setTimeout(() => this.$().modal('hide'));
  }

  destroy() {
    this.component = null;
    m.redraw();
  }

  ready() {
    this.component && this.component.ready && this.component.ready(this.$());
  }
}
