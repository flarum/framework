import MentionFormat from './MentionFormat';
import MentionableModel from '../MentionableModel';
import TagMention from '../TagMention';

export default class HashMentionFormat extends MentionFormat {
  public mentionables: (new (...args: any[]) => MentionableModel)[] = [TagMention];
  protected extendable: boolean = true;

  public trigger(): string {
    return '#';
  }

  public queryFromTyped(typed: string): string | null {
    const matchTyped = typed.match(/^[-_\p{L}\p{N}\p{M}]+$/giu);

    return matchTyped ? matchTyped[0] : null;
  }

  public format(slug: string): string {
    return `#${slug}`;
  }
}
