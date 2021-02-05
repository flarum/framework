import Component from 'flarum/Component';

const modifierKey = navigator.userAgent.match(/Macintosh/) ? 'Meta' : 'Control';

export default class MarkdownToolbar extends Component {
  oncreate(vnode) {
    super.oncreate(vnode);

    this.attrs.setShortcutHandler(this.shortcut.bind(this));
  }

  view(vnode) {
    return <div id="MarkdownToolbar" data-for={this.attrs.for} style={{ display: 'inline-block' }}>
      {vnode.children}
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
