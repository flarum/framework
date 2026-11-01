import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import type { Release } from '../types';
import type Mithril from 'mithril';
export interface IReleaseNotesModalAttrs extends IInternalModalAttrs {
    name: string;
    package: string;
    from: string;
    to: string;
}
/**
 * What an update gives you: every GitHub release between the installed
 * version and the one on offer, fetched by the server when this opens.
 */
export default class ReleaseNotesModal extends Modal<IReleaseNotesModalAttrs> {
    loading: boolean;
    releases: Release[];
    url: string | null;
    failed: boolean;
    oninit(vnode: Mithril.Vnode<IReleaseNotesModalAttrs, this>): void;
    className(): string;
    title(): any[];
    content(): JSX.Element;
    body(): Mithril.Children;
}
