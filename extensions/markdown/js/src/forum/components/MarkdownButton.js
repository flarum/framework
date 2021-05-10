import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';

export default class MarkdownButton extends Component {
  oncreate(vnode) {
    super.oncreate(vnode);

    this.$().tooltip();
  }

  view() {
    return (
      <button className="Button Button--icon Button--link" title={this.attrs.title} data-hotkey={this.attrs.hotkey}
        onkeydown={this.keydown.bind(this)} onclick={this.attrs.onclick}>
        {icon(this.attrs.icon)}
      </button>
    );
  }

  keydown(event) {
    if (event.key === ' ' || event.key === 'Enter') {
      event.preventDefault();
      this.element.click();
    }
  }
}
