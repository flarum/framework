import Component from 'flarum/common/Component';
import { ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
export default class ControlSection extends Component<ComponentAttrs> {
    oninit(vnode: Mithril.Vnode<ComponentAttrs, this>): void;
    view(): JSX.Element;
}
