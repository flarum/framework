import Component from 'flarum/common/Component';
import type { ComponentAttrs } from 'flarum/common/Component';
import classList from 'flarum/common/utils/classList';
import type MentionableModel from '../mentionables/MentionableModel';
import type Mithril from 'mithril';

export interface IMentionsDropdownItemAttrs extends ComponentAttrs {
  mentionable: MentionableModel;
  onclick: () => void;
  onmouseenter: () => void;
}

export default class MentionsDropdownItem<CustomAttrs extends IMentionsDropdownItemAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs>): Mithril.Children {
    const { mentionable, ...attrs } = this.attrs;

    const className = classList('MentionsDropdownItem', 'PostPreview', `MentionsDropdown-${mentionable.type()}`);

    return (
      <button className={className} {...attrs}>
        <span className="PostPreview-content">{vnode.children}</span>
      </button>
    );
  }
}
