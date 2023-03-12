import app from 'flarum/forum/app';
import Badge from 'flarum/common/components/Badge';
import highlight from 'flarum/common/helpers/highlight';
import type Tag from 'flarum/tags/common/models/Tag';
import type Mithril from 'mithril';
import MentionableModel from './MentionableModel';
import type HashMentionFormat from './formats/HashMentionFormat';

export default class TagMention extends MentionableModel<Tag, HashMentionFormat> {
  type(): string {
    return 'tag';
  }

  initialResults(): Tag[] {
    return Array.from(app.store.all<Tag>('tags'));
  }

  /**
   * Generates the mention syntax for a tag mention.
   *
   * ~tagSlug
   *
   * @example <caption>Tag mention</caption>
   * // ~general
   * forTag(tag) // Tag display name is 'Tag', tag ID is 5
   */
  public replacement(tag: Tag): string {
    return this.format.format(tag.slug());
  }

  matches(model: Tag, typed: string): boolean {
    if (!typed) return false;

    const names = [model.name().toLowerCase()];

    return names.some((name) => name.toLowerCase().substr(0, typed.length) === typed);
  }

  maxStoreMatchedResults(): null {
    return null;
  }

  async search(typed: string): Promise<Tag[]> {
    return await app.store.find<Tag[]>('tags', { filter: { q: typed }, page: { limit: 5 } });
  }

  suggestion(model: Tag, typed: string): Mithril.Children {
    let tagName: Mithril.Children = model.name();

    if (typed) {
      tagName = highlight(tagName, typed);
    }

    return (
      <>
        <Badge className="Avatar" icon={model.icon()} color={model.color()} type="tag" />
        <span className="username">{tagName}</span>
      </>
    );
  }

  enabled(): boolean {
    return 'flarum-tags' in flarum.extensions;
  }
}
