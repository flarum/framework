import Component from 'flarum/Component';

const modifierKey = navigator.userAgent.match(/Macintosh/) ? 'Meta' : 'Control';

export default class MarkdownToolbar extends Component {
  config(isInitialized) {
    if (isInitialized) return;

    const field = document.getElementById(this.props.for);

    field.addEventListener('keydown', this.shortcut.bind(this));
  }

  view() {
    return <div id="MarkdownToolbar" data-for={this.props.for} style={{ display: 'inline-block' }}>
      {this.props.children}
    </div>;
  }

  shortcut(event) {
    if ((event.metaKey && modifierKey === 'Meta') || (event.ctrlKey && modifierKey === 'Control')) {
      const button = this.element.querySelector(`[data-hotkey="${event.key}"]`);

      if (button) {
        button.click();
        event.preventDefault()
      }
    }
  }
}
