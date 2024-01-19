import type ForumApplication from 'flarum/forum/ForumApplication';
import type IExtender from 'flarum/common/extenders/IExtender';
import type MentionableModel from '../mentionables/MentionableModel';
import type MentionFormat from '../mentionables/formats/MentionFormat';

export default class Mentionables implements IExtender<ForumApplication> {
  protected formats: (new () => MentionFormat)[] = [];
  protected mentionables: Record<string, (new (...args: any[]) => MentionableModel)[]> = {};

  /**
   * Register a new mention format.
   * Must extend MentionFormat and have a unique unused trigger symbol.
   */
  format(format: new () => MentionFormat): this {
    this.formats.push(format);

    return this;
  }

  /**
   * Register a new mentionable model to a mention format.
   * Only works if the format has already been registered,
   *  and the format allows using multiple mentionables.
   *
   * @param symbol The trigger symbol of the format to extend (ex: @).
   * @param mentionable The mentionable instance to register.
   *                    Must extend MentionableModel.
   */
  mentionable(symbol: string, mentionable: new (...args: any[]) => MentionableModel): this {
    if (!this.mentionables[symbol]) {
      this.mentionables[symbol] = [];
    }

    this.mentionables[symbol].push(mentionable);

    return this;
  }

  extend(app: ForumApplication): void {
    for (const format of this.formats) {
      app.mentionFormats.extend(format);
    }

    for (const symbol in this.mentionables) {
      const format = app.mentionFormats.get(symbol);

      if (!format) continue;

      for (const mentionable of this.mentionables[symbol]) {
        format.extend(mentionable);
      }
    }
  }
}
