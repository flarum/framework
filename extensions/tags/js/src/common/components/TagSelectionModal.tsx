import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import classList from 'flarum/common/utils/classList';

import type Mithril from 'mithril';
import Tag from '../models/Tag';

export type TagResultFormat = 'slug' | 'id' | 'model';
export interface TagResultFormatType extends Record<TagResultFormat, any> {
  slug: string;
  id: string | number;
  model: Tag;
}

interface ITagSelectionModalAttrs<ResultFormat extends TagResultFormat> extends IInternalModalAttrs {
  /**
   * The format for results to be returned in.
   */
  tagResultFormat: ResultFormat;

  /**
   * Modal title.
   */
  title: Mithril.Children;

  /**
   * Optional class for the modal.
   */
  className?: string;

  /**
   * This should return a method to be used for filtering the list of available tags.
   *
   * Defaults to a method that returns all tags.
   *
   * **Only primary tags:**
   * ```ts
   * filterAvailableTags: () => {
   *   return (tag: Tag) => {
   *     return tag.postion() !== null;
   *   }
   * }
   * ```
   *
   * **Only children of a primary tag already selected:**
   * ```ts
   * filterAvailableTags: (_: Tag[], selectedTags: Tag[]) => {
   *   if (selectedTags.length === 0) return () => true;
   *
   *   const parentTag = selectedTags.find(t => !t.parent());
   *
   *   return (tag: Tag) => {
   *     return tag.parent() === parentTag;
   *   }
   * }
   * ```
   */
  filterAvailableTags?: (allTags: Tag[], selectedTags: Tag[]) => (tag: Tag) => boolean;

  /**
   * Called before a tag is added to the list of selected tags.
   *
   * Allows for manipulation of the list based on the tag being added.
   *
   * The tag array returned will be the new list of selected tags.
   *
   * @param currentlySelectedTags The tags that are currently selected.
   * @param addingTag The tag about to be added to the selected tags.
   */
  onTagAdding?: (currentlySelectedTags: Tag[], addingTag: Tag) => Tag[];

  /**
   * Called before a tag is removed from the list of selected tags.
   *
   * Allows for manipulation of the list based on the tag being removed.
   *
   * The tag array returned will be the new list of selected tags.
   *
   * @param currentlySelectedTags The tags that are currently selected.
   * @param removingTag The tag about to be removed from the selected tags.
   */
  onTagRemoving?: (currentlySelectedTags: Tag[], removingTag: Tag) => Tag[];

  value: TagResultFormatType[ResultFormat][];
}

const defaultAttrs: Partial<ITagSelectionModalAttrs<TagResultFormat>> = {
  filterAvailableTags: () => () => true,
  onTagAdding: (currentlySelectedTags, addingTag) => [...currentlySelectedTags, addingTag],
  onTagRemoving: (currentlySelectedTags, removingTag) => currentlySelectedTags.filter((t) => t !== removingTag),
};

export default class TagSelectionModal<ResultFormat extends TagResultFormat> extends Modal<ITagSelectionModalAttrs<ResultFormat>> {
  protected getAttr(attr: keyof ITagSelectionModalAttrs<ResultFormat>) {
    return this.attrs[attr] ?? defaultAttrs[attr];
  }

  className() {
    return classList('TagSelectionModal Modal--small', this.attrs.className);
  }

  title() {
    return this.attrs.title;
  }

  content(): Mithril.Children {
    return <div className="Modal-body">Test</div>;
  }
}
