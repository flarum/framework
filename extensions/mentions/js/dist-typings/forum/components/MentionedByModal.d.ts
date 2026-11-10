import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
import type Post from 'flarum/common/models/Post';
import MentionedByModalState from '../state/MentionedByModalState';
export interface IMentionedByModalAttrs extends IInternalModalAttrs {
    post: Post;
}
export default class MentionedByModal<CustomAttrs extends IMentionedByModalAttrs = IMentionedByModalAttrs> extends Modal<CustomAttrs, MentionedByModalState> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
}
