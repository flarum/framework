import Modal from 'flarum/common/components/Modal';
import type { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Post from 'flarum/common/models/Post';
import type Mithril from 'mithril';
import PostLikesModalState from '../states/PostLikesModalState';
export interface IPostLikesModalAttrs extends IInternalModalAttrs {
    post: Post;
}
export default class PostLikesModal<CustomAttrs extends IPostLikesModalAttrs = IPostLikesModalAttrs> extends Modal<CustomAttrs, PostLikesModalState> {
    oninit(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
}
