import MentionFormat from './MentionFormat';
import type MentionableModel from '../MentionableModel';
import UserMention from '../UserMention';
import PostMention from '../PostMention';
import GroupMention from '../GroupMention';

export default class AtMentionFormat extends MentionFormat {
  public mentionables: (new (...args: any[]) => MentionableModel)[] = [UserMention, PostMention, GroupMention];
  protected extendable: boolean = true;

  public trigger(): string {
    return '@';
  }

  public queryFromTyped(typed: string): string | null {
    const matchTyped = typed.match(/^["“]?((?:(?!"#).)+)$/);

    return matchTyped ? matchTyped[1] : null;
  }

  public format(name: string, char: string | null = '', id: string | null = null): string {
    return {
      simple: `@${name}`,
      safe: `@"${name}"#${char}${id}`,
    }[id ? 'safe' : 'simple'];
  }
}
