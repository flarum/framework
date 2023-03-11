import type Mithril from 'mithril';
import type Model from 'flarum/common/Model';
import type MentionFormat from './formats/MentionFormat';

export default abstract class MentionableModel<M extends Model = Model, Format extends MentionFormat = MentionFormat> {
  public format: Format;

  public constructor(format: Format) {
    this.format = format;
  }

  abstract type(): string;
  abstract initialResults(): M[];
  abstract search(typed: string): Promise<M[]>;
  abstract replacement(model: M): string;
  abstract suggestion(model: M, typed: string): Mithril.Children;
  abstract matches(model: M, typed: string): boolean;
  abstract maxStoreMatchedResults(): number | null;
  abstract enabled(): boolean;
}
