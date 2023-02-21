import KeyboardNavigatable from 'flarum/common/utils/KeyboardNavigatable';
import Modal from 'flarum/common/components/Modal';
import Stream from 'flarum/common/utils/Stream';
import type Tag from '../models/Tag';
import type { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
export interface ITagSelectionModalLimits {
    /** Whether to allow bypassing the limits set here. This will show a toggle button to bypass limits. */
    allowBypassing?: boolean;
    /** Maximum number of primary/secondary tags allowed. */
    max?: {
        total?: number;
        primary?: number;
        secondary?: number;
    };
    /** Minimum number of primary/secondary tags to be selected. */
    min?: {
        total?: number;
        primary?: number;
        secondary?: number;
    };
}
export interface ITagSelectionModalAttrs extends IInternalModalAttrs {
    /** Custom modal className to use. */
    className?: string;
    /** Modal title, defaults to 'Choose Tags'. */
    title?: string;
    /** Initial tag selection value. */
    selectedTags?: Tag[];
    /** Limits set based on minimum and maximum number of primary/secondary tags that can be selected. */
    limits?: ITagSelectionModalLimits;
    /** Whether to allow resetting the value. Defaults to true. */
    allowResetting?: boolean;
    /** Whether to require the parent tag of a selected tag to be selected as well. */
    requireParentTag?: boolean;
    /** Filter tags that can be selected. */
    selectableTags?: (tags: Tag[]) => Tag[];
    /** Whether a tag can be selected. */
    canSelect: (tag: Tag) => boolean;
    /** Callback for when a tag is selected. */
    onSelect?: (tag: Tag, selected: Tag[]) => void;
    /** Callback for when a tag is deselected. */
    onDeselect?: (tag: Tag, selected: Tag[]) => void;
    /** Callback for when the selection is submitted. */
    onsubmit?: (selected: Tag[]) => void;
}
export declare type ITagSelectionModalState = undefined;
export default class TagSelectionModal<CustomAttrs extends ITagSelectionModalAttrs = ITagSelectionModalAttrs, CustomState extends ITagSelectionModalState = ITagSelectionModalState> extends Modal<CustomAttrs, CustomState> {
    protected loading: boolean;
    protected tags: Tag[];
    protected selected: Tag[];
    protected bypassReqs: boolean;
    protected filter: Stream<string>;
    protected focused: boolean;
    protected navigator: KeyboardNavigatable;
    protected indexTag?: Tag;
    static initAttrs(attrs: ITagSelectionModalAttrs): void;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): string | undefined;
    content(): JSX.Element | JSX.Element[];
    /**
     * Filters the available tags on every state change.
     */
    private getFilteredTags;
    /**
     * Counts the number of selected primary tags.
     */
    protected primaryCount(): number;
    /**
     * Counts the number of selected secondary tags.
     */
    protected secondaryCount(): number;
    /**
     * Validates the number of selected primary/secondary tags against the set min max limits.
     */
    protected meetsRequirements(primaryCount: number, secondaryCount: number): boolean;
    /**
     * Add the given tag to the list of selected tags.
     */
    protected addTag(tag: Tag | undefined): void;
    /**
     * Remove the given tag from the list of selected tags.
     */
    protected removeTag(tag: Tag): void;
    protected toggleTag(tag: Tag): void;
    /**
     * Gives human text instructions based on the current number of selected tags and set limits.
     */
    protected getInstruction(primaryCount: number, secondaryCount: number): string;
    /**
     * Submit tag selection.
     */
    onsubmit(e: SubmitEvent): void;
    protected select(e: KeyboardEvent): void;
    protected selectableItems(): JQuery<HTMLElement>;
    protected getCurrentNumericIndex(): number;
    protected getItem(selectedTag: Tag): JQuery<HTMLElement>;
    protected setIndex(index: number, scrollToItem: boolean): void;
}
