/// <reference types="flarum/@types/translator-icu-rich" />
import type Mithril from 'mithril';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import KeyboardNavigatable from 'flarum/forum/utils/KeyboardNavigatable';
import Stream from 'flarum/common/utils/Stream';
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
    filter: Stream<string>;
    focused: boolean;
    minPrimary: number;
    maxPrimary: number;
    minSecondary: number;
    maxSecondary: number;
    bypassReqs: boolean;
    navigator: KeyboardNavigatable;
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
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    getInstruction(primaryCount: number, secondaryCount: number): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element | JSX.Element[];
    meetsRequirements(primaryCount: number, secondaryCount: number): boolean;
    toggleTag(tag: Tag): void;
    select(e: KeyboardEvent): void;
    selectableItems(): JQuery<HTMLElement>;
    getCurrentNumericIndex(): number;
    getItem(selectedTag: Tag): JQuery<HTMLElement>;
    setIndex(index: number, scrollToItem: boolean): void;
    onsubmit(e: SubmitEvent): void;
}
