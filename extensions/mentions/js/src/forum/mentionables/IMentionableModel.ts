import type Mithril from 'mithril';
import type Model from 'flarum/common/Model';

export default interface IMentionableModel<T extends Model> {
  type(): string;
  initialResults(): T[];
  search(typed: string): Promise<T[]>;
  replacement(model: T): string;
  suggestion(model: T, typed: string): Mithril.Children;
  matches(model: T, typed: string): boolean;
  maxStoreMatchedResults(): number | null;
  enabled(): boolean;
}
