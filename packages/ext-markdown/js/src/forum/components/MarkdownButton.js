import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';
import apply from '../util/apply';

const modifierKey = navigator.userAgent.match(/Macintosh/) ? '⌘' : 'ctrl';

export default class MarkdownButton extends Component {
  oncreate(vnode) {
    super.oncreate(vnode);

    this.$().tooltip();
  }

  view() {
    return (
      <button className="Button Button--icon Button--link" title={this.title()} data-hotkey={this.attrs.hotkey}
        onclick={this.click.bind(this)} onkeydown={this.keydown.bind(this)}>
        {icon(this.attrs.icon)}
      </button>
    );
  }

  keydown(event) {
    if (event.key === ' ' || event.key === 'Enter') {
      event.preventDefault();
      this.click();
    }
  }

  click() {
    return apply(this.element, this.attrs.style);
  }

  title() {
    let tooltip = this.attrs.title;

    if (this.attrs.hotkey) tooltip += ` <${modifierKey}-${this.attrs.hotkey}>`;

    return tooltip;
  }
}
