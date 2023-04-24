import type MentionableModel from './MentionableModel';
import type Model from 'flarum/common/Model';
import type Mithril from 'mithril';
import MentionsDropdownItem from '../components/MentionsDropdownItem';
import { throttle } from 'flarum/common/utils/throttleDebounce';

export default class MentionableModels {
  protected mentionables?: MentionableModel[];
  /**
   * We store models returned from an API here to preserve order in which they are returned
   * This prevents the list jumping around while models are returned.
   * We also use a hashmap for model IDs to provide O(1) lookup for the users already in the list.
   */
  private results: Record<string, Map<string, Model>> = {};
  public typed: string | null = null;
  private searched: string[] = [];
  private dropdownItemAttrs: Record<string, any> = {};

  constructor(dropdownItemAttrs: Record<string, any>) {
    this.dropdownItemAttrs = dropdownItemAttrs;
  }

  public init(mentionables: MentionableModel[]): void {
    this.typed = null;
    this.mentionables = mentionables;

    for (const mentionable of this.mentionables) {
      this.results[mentionable.type()] = new Map(mentionable.initialResults().map((result) => [result.id() as string, result]));
    }
  }

  /**
   * Don't send API calls searching for models until at least 2 characters have been typed.
   * This focuses the mention results on models already loaded.
   */
  public readonly search = throttle(250, async (): Promise<void> => {
    if (!this.typed || this.typed.length <= 1) return;

    const typedLower = this.typed.toLowerCase();

    if (this.searched.includes(typedLower)) return;

    for (const mentionable of this.mentionables!) {
      for (const model of await mentionable.search(typedLower)) {
        if (!this.results[mentionable.type()].has(model.id() as string)) {
          this.results[mentionable.type()].set(model.id() as string, model);
        }
      }
    }

    this.searched.push(typedLower);

    return Promise.resolve();
  });

  public matches(mentionable: MentionableModel, model: Model): boolean {
    return mentionable.matches(model, this.typed?.toLowerCase() || '');
  }

  public makeSuggestion(mentionable: MentionableModel, model: Model): Mithril.Children {
    const content = mentionable.suggestion(model, this.typed!);
    const replacement = mentionable.replacement(model);

    const { onclick, ...attrs } = this.dropdownItemAttrs;

    return (
      <MentionsDropdownItem mentionable={mentionable} onclick={() => onclick(replacement)} {...attrs}>
        {content}
      </MentionsDropdownItem>
    );
  }

  public buildSuggestions(): Mithril.Children {
    const suggestions: Mithril.Children = [];

    for (const mentionable of this.mentionables!) {
      if (!mentionable.enabled()) continue;

      let matches = Array.from(this.results[mentionable.type()].values()).filter((model) => this.matches(mentionable, model));

      const max = mentionable.maxStoreMatchedResults();
      if (max) matches = matches.splice(0, max);

      for (const model of matches) {
        const dropdownItem = this.makeSuggestion(mentionable, model);
        suggestions.push(dropdownItem);
      }
    }

    return suggestions;
  }
}
