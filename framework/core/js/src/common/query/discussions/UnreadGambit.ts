import app from '../../app';
import IGambit, { GambitType } from '../IGambit';

export default class UnreadGambit implements IGambit<GambitType.Grouped> {
  type = GambitType.Grouped;

  pattern(): string {
    return 'is:unread';
  }

  toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'unread';

    return {
      [key]: true,
    };
  }

  filterKey(): string {
    return 'unread';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}is:unread`;
  }

  suggestion() {
    return {
      group: 'is',
      key: app.translator.trans('core.lib.gambits.discussions.unread.key', {}, true),
    };
  }
}
