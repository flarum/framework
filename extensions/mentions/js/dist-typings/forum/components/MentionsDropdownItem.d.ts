import Component from 'flarum/common/Component';
import type { ComponentAttrs } from 'flarum/common/Component';
import type MentionableModel from '../mentionables/MentionableModel';
import type Mithril from 'mithril';
export interface IMentionsDropdownItemAttrs extends ComponentAttrs {
    mentionable: MentionableModel;
    onclick: () => void;
    onmouseenter: () => void;
}
export default class MentionsDropdownItem<CustomAttrs extends IMentionsDropdownItemAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs>): Mithril.Children;
}
