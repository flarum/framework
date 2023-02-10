import type Mithril from 'mithril';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Stream from 'flarum/common/utils/Stream';
export interface InstallerAttrs extends ComponentAttrs {
}
export declare type InstallerLoadingTypes = 'extension-install' | null;
export default class Installer extends Component<InstallerAttrs> {
    packageName: Stream<string>;
    oninit(vnode: Mithril.Vnode<InstallerAttrs, this>): void;
    view(): Mithril.Children;
    data(): any;
    onsubmit(): void;
}
