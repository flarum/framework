import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
export interface IMarkdownButtonAttrs extends ComponentAttrs {
    icon: string;
    onclick: () => void;
    title?: string;
    hotkey?: string;
}
export default class MarkdownButton extends Component<IMarkdownButtonAttrs> {
    oncreate(vnode: Mithril.VnodeDOM<IMarkdownButtonAttrs, this>): void;
    view(): JSX.Element;
    keydown(event: KeyboardEvent): void;
}
