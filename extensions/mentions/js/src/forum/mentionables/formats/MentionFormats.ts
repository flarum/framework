import AtMentionFormat from './AtMentionFormat';
import HashMentionFormat from './HashMentionFormat';
import type MentionFormat from './MentionFormat';
import MentionableModel from '../MentionableModel';

export default class MentionFormats {
  protected formats: MentionFormat[] = [new AtMentionFormat(), new HashMentionFormat()];

  public get(symbol: string): MentionFormat | null {
    return this.formats.find((f) => f.trigger() === symbol) ?? null;
  }

  public mentionable(type: string): MentionableModel | null {
    for (const format of this.formats) {
      const mentionable = format.getMentionable(type);

      if (mentionable) return mentionable;
    }

    return null;
  }

  public extend(format: new () => MentionFormat) {
    this.formats.push(new format());
  }
}
