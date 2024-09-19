import app from 'flarum/common/app';
import { KeyValueGambit } from 'flarum/common/query/IGambit';

export default class TagGambit extends KeyValueGambit {
  predicates = true;

  key(): string {
    return app.translator.trans('flarum-tags.lib.gambits.discussions.tag.key', {}, true);
  }

  hint(): string {
    return app.translator.trans('flarum-tags.lib.gambits.discussions.tag.hint', {}, true);
  }

  filterKey(): string {
    return 'tag';
  }

  gambitValueToFilterValue(value: string): string[] {
    return [value];
  }

  fromFilter(value: any, negate: boolean): string {
    let gambits = [];

    if (Array.isArray(value)) {
      gambits = value.map((value) => this.fromFilter(value.toString(), negate));
    } else {
      return `${negate ? '-' : ''}${this.key()}:${this.filterValueToGambitValue(value)}`;
    }

    return gambits.join(' ');
  }

  filterValueToGambitValue(value: string): string {
    return value;
  }
}
