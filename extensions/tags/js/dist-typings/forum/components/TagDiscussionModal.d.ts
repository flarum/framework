import type Mithril from 'mithril';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Discussion from 'flarum/common/models/Discussion';
import Tag from '../../common/models/Tag';
export interface TagDiscussionModalAttrs extends IInternalModalAttrs {
    discussion?: Discussion;
    selectedTags?: Tag[];
    onsubmit?: (tags: Tag[]) => {};
}
export default class TagDiscussionModal extends Modal<TagDiscussionModalAttrs> {
    tagsLoading: boolean;
    selected: Tag[];
    filter: any;
    focused: boolean;
    minPrimary: any;
    maxPrimary: any;
    minSecondary: any;
    maxSecondary: any;
    bypassReqs: boolean;
    navigator: any;
    tags?: Tag[];
    selectedTag?: Tag;
    oninit(vnode: Mithril.Vnode<TagDiscussionModalAttrs, this>): void;
    primaryCount(): number;
    secondaryCount(): number;
    /**
     * Add the given tag to the list of selected tags.
     */
    addTag(tag: Tag): void;
    /**
     * Remove the given tag from the list of selected tags.
     */
    removeTag(tag: Tag): void;
    className(): string;
    title(): any;
    getInstruction(primaryCount: number, secondaryCount: number): any;
    content(): JSX.Element | JSX.Element[];
    meetsRequirements(primaryCount: number, secondaryCount: number): boolean;
    toggleTag(tag: Tag): void;
    select(e: KeyboardEvent): void;
    selectableItems(): any;
    getCurrentNumericIndex(): any;
    getItem(selectedTag: Tag): any;
    setIndex(index: number, scrollToItem: boolean): void;
    onsubmit(e: SubmitEvent): void;
}
