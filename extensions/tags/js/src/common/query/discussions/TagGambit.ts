import app from 'flarum/common/app';
import IGambit, { GambitType } from 'flarum/common/query/IGambit';

export default class TagGambit implements IGambit<GambitType.KeyValue> {
  type = GambitType.KeyValue;

  pattern(): string {
    return 'tag:(.+)';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'tag';

    return {
      [key]: matches[1].split(','),
    };
  }

  filterKey(): string {
    return 'tag';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}tag:${value}`;
  }

  suggestion() {
    return {
      key: app.translator.trans('flarum-tags.lib.gambits.discussions.tag.key', {}, true),
      hint: app.translator.trans('flarum-tags.lib.gambits.discussions.tag.hint', {}, true),
    };
  }
}
