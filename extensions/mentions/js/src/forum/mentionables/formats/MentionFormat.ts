import type MentionableModel from '../MentionableModel';
import type Model from 'flarum/common/Model';

export default abstract class MentionFormat {
  protected instances?: MentionableModel[];

  public makeMentionables(): MentionableModel[] {
    return this.instances ?? (this.instances = this.mentionables.map((Mentionable) => new Mentionable(this)));
  }

  public getMentionable(type: string): MentionableModel | null {
    return this.makeMentionables().find((mentionable) => mentionable.type() === type) ?? null;
  }

  public extend(mentionable: new (...args: any[]) => MentionableModel): void {
    if (!this.extendable) throw new Error('This mention format does not allow extending.');

    this.mentionables.push(mentionable);
  }

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
