import type Mithril from 'mithril';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Stream from 'flarum/common/utils/Stream';
interface InstallerAttrs extends ComponentAttrs {
}
export default class Installer extends Component<InstallerAttrs> {
    packageName: Stream<string>;
    isLoading: boolean;
    oninit(vnode: Mithril.Vnode<InstallerAttrs, this>): void;
    view(): Mithril.Children;
    data(): any;
    onsubmit(): void;
}
export {};
