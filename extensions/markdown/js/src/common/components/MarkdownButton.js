import Component from 'flarum/common/Component';
import icon from 'flarum/common/helpers/icon';
import Tooltip from 'flarum/common/components/Tooltip';

export default class MarkdownButton extends Component {
  oncreate(vnode) {
    super.oncreate(vnode);
  }

  view() {
    const button = (
      <button
        className="Button Button--icon Button--link"
        type="button"
        data-hotkey={this.attrs.hotkey}
        onkeydown={this.keydown.bind(this)}
        onclick={this.attrs.onclick}
      >
        {icon(this.attrs.icon)}
      </button>
    );

    if (this.attrs.title) {
      return <Tooltip text={this.attrs.title}>{button}</Tooltip>;
    }

    return button;
  }

  keydown(event) {
    if (event.key === ' ' || event.key === 'Enter') {
      event.preventDefault();
      this.element.click();
    }
  }
}
