import type MentionableModel from '../MentionableModel';
export default abstract class MentionFormat {
    protected instances?: MentionableModel[];
    makeMentionables(): MentionableModel[];
    getMentionable(type: string): MentionableModel | null;
    extend(mentionable: new (...args: any[]) => MentionableModel): void;
    abstract mentionables: (new (...args: any[]) => MentionableModel)[];
    protected abstract extendable: boolean;
    abstract trigger(): string;
    /**
     * Picks the term to search in the API from the typed text.
     * @example:
     *  * Full text = `Hello @"John D`
     *  * Typed text = `"John D`
     *  * Query = `John D`
     */
    abstract queryFromTyped(typed: string): string | null;
    abstract format(...args: any): string;
}
