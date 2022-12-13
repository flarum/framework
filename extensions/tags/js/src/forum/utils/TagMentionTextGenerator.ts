import type Tag from '../../common/models/Tag';

/**
 * Fetches the mention text for a specified model.
 */
export default class TagMentionTextGenerator {
  /**
   * Generates the mention syntax for a tag mention.
   *
   * @example <caption>Tag mention</caption>
   * // '#"General"#t1'
   * // #"Name"#tTagID
   * forTag(tag) // Tag display name is 'General', tag ID is 1
   *
   * @param tag
   * @returns
   */
  forTag(tag: Tag): string {
    return `#"${tag.name()}"#t${tag.id()}`;
  }
}
