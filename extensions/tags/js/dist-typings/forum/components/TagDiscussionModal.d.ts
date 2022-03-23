export default class TagDiscussionModal extends Modal<import("flarum/common/components/Modal").IInternalModalAttrs> {
    constructor();
    oninit(vnode: any): void;
    tagsLoading: boolean | undefined;
    selected: any[] | undefined;
    filter: Stream<string> | undefined;
    focused: boolean | undefined;
    minPrimary: any;
    maxPrimary: any;
    minSecondary: any;
    maxSecondary: any;
    bypassReqs: any;
    navigator: KeyboardNavigatable | undefined;
    tags: any;
    index: any;
    primaryCount(): number;
    secondaryCount(): number;
    /**
     * Add the given tag to the list of selected tags.
     *
     * @param {Tag} tag
     */
    addTag(tag: Tag): void;
    /**
     * Remove the given tag from the list of selected tags.
     *
     * @param {Tag} tag
     */
    removeTag(tag: Tag): void;
    title(): any;
    getInstruction(primaryCount: any, secondaryCount: any): any;
    content(): JSX.Element | JSX.Element[];
    meetsRequirements(primaryCount: any, secondaryCount: any): boolean;
    toggleTag(tag: any): void;
    select(e: any): void;
    selectableItems(): JQuery<HTMLElement>;
    getCurrentNumericIndex(): number;
    getItem(index: any): JQuery<HTMLElement>;
    setIndex(index: any, scrollToItem: any): void;
    onsubmit(e: any): void;
}
import Modal from "flarum/common/components/Modal";
import Stream from "flarum/common/utils/Stream";
import KeyboardNavigatable from "flarum/forum/utils/KeyboardNavigatable";
