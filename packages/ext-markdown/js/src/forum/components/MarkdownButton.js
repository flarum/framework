import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';
import apply from '../util/apply';

const modifierKey = navigator.userAgent.match(/Macintosh/) ? '⌘' : 'ctrl';

export default class MarkdownButton extends Component {
  config(isInitialized) {
    if (isInitialized) return;

    this.$().tooltip();
  }

  view() {
    return <button className="Button Button--icon Button--link" title={this.title()} data-hotkey={this.props.hotkey}
                   onclick={this.click.bind(this)} onkeydown={this.keydown.bind(this)}>
      {icon(this.props.icon)}
    </button>;
  }

  keydown(event) {
    if (event.key === ' ' || event.key === 'Enter') {
      event.preventDefault();
      this.click();
    }
  }

  click() {
    return apply(this.element, this.props.style);
  }

  title() {
    let tooltip = this.props.title;

    if (this.props.hotkey) tooltip += ` <${modifierKey}-${this.props.hotkey}>`;

    return tooltip;
  }
}
