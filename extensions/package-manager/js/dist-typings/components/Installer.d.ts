import type Mithril from 'mithril';
import Component from 'flarum/common/Component';
import Stream from 'flarum/common/utils/Stream';
export default class Installer<Attrs> extends Component<Attrs> {
    packageName: Stream<string>;
    isLoading: boolean;
    oninit(vnode: Mithril.Vnode<Attrs, this>): void;
    view(): Mithril.Children;
    data(): any;
    onsubmit(): void;
}
