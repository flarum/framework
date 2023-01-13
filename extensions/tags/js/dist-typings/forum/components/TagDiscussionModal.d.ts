import TagSelectionModal, { ITagSelectionModalAttrs } from '../../common/components/TagSelectionModal';
import type Discussion from 'flarum/common/models/Discussion';
export interface TagDiscussionModalAttrs extends ITagSelectionModalAttrs {
    discussion?: Discussion;
}
export default class TagDiscussionModal extends TagSelectionModal<TagDiscussionModalAttrs> {
    static initAttrs(attrs: TagDiscussionModalAttrs): void;
}
