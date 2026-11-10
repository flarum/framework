import Component, { ComponentAttrs } from 'flarum/common/Component';
import Icon from 'flarum/common/components/Icon';
import Tooltip from 'flarum/common/components/Tooltip';
import type Mithril from 'mithril';

export interface IMarkdownButtonAttrs extends ComponentAttrs {
  icon: string;
  onclick: () => void;
  title?: string;
  hotkey?: string;
}

export default class MarkdownButton extends Component<IMarkdownButtonAttrs> {
  oncreate(vnode: Mithril.VnodeDOM<IMarkdownButtonAttrs, this>) {
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
        <Icon name={this.attrs.icon} />
      </button>
    );

    if (this.attrs.title) {
      return <Tooltip text={this.attrs.title}>{button}</Tooltip>;
    }

    return button;
  }

  keydown(event: KeyboardEvent) {
    if (event.key === ' ' || event.key === 'Enter') {
      event.preventDefault();
      (this.element as HTMLElement).click();
    }
  }
}
