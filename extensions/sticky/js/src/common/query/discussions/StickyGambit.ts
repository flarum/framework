import IGambit, { GambitType } from 'flarum/common/query/IGambit';
import app from 'flarum/common/app';

export default class StickyGambit implements IGambit<GambitType.Grouped> {
  type = GambitType.Grouped;

  pattern(): string {
    return 'is:sticky';
  }

  toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'sticky';

    return {
      [key]: true,
    };
  }

  filterKey(): string {
    return 'sticky';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}is:sticky`;
  }

  suggestion() {
    return {
      group: 'is',
      key: app.translator.trans('flarum-sticky.lib.gambits.discussions.sticky.key', {}, true),
    };
  }
}
