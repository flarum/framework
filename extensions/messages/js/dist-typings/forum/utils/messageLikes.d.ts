import type Mithril from 'mithril';
import type DialogMessage from '../../common/models/DialogMessage';
export declare function likeButton(message: DialogMessage): Mithril.Vnode<any, any> | null;
export declare function toggleLike(message: DialogMessage): Promise<void>;
export declare function likedBy(message: DialogMessage): Mithril.Vnode<any, any> | null;
