import app from 'flarum/common/app';
import IGambit, { GambitType } from 'flarum/common/query/IGambit';

export default class SuspendedGambit implements IGambit<GambitType> {
  type = GambitType.Grouped;

  pattern(): string {
    return 'is:suspended';
  }

  toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'suspended';

    return {
      [key]: true,
    };
  }

  filterKey(): string {
    return 'suspended';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}is:suspended`;
  }

  suggestion() {
    return {
      group: 'is',
      key: app.translator.trans('flarum-suspend.lib.gambits.users.suspended.key', {}, true),
    };
  }
}
