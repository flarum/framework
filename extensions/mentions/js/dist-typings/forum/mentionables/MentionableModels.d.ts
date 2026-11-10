import type MentionableModel from './MentionableModel';
import type Model from 'flarum/common/Model';
import type Mithril from 'mithril';
export default class MentionableModels {
    protected mentionables?: MentionableModel[];
    /**
     * We store models returned from an API here to preserve order in which they are returned
     * This prevents the list jumping around while models are returned.
     * We also use a hashmap for model IDs to provide O(1) lookup for the users already in the list.
     */
    private results;
    typed: string | null;
    private searched;
    private dropdownItemAttrs;
    constructor(dropdownItemAttrs: Record<string, any>);
    init(mentionables: MentionableModel[]): void;
    /**
     * Don't send API calls searching for models until at least 2 characters have been typed.
     * This focuses the mention results on models already loaded.
     */
    readonly search: () => Promise<void>;
    matches(mentionable: MentionableModel, model: Model): boolean;
    makeSuggestion(mentionable: MentionableModel, model: Model): Mithril.Children;
    buildSuggestions(): Mithril.Children;
}
