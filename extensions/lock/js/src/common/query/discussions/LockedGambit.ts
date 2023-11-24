import IGambit, { GambitType } from 'flarum/common/query/IGambit';
import app from 'flarum/common/app';

export default class LockedGambit implements IGambit<GambitType.Grouped> {
  type = GambitType.Grouped;

  pattern(): string {
    return 'is:locked';
  }

  toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'locked';

    return {
      [key]: true,
    };
  }

  filterKey(): string {
    return 'locked';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}is:locked`;
  }

  suggestion() {
    return {
      group: 'is',
      key: app.translator.trans('flarum-lock.lib.gambits.discussions.locked.key', {}, true),
    };
  }
}
