import app from '../../app';
import IGambit, { GambitType } from '../IGambit';

export default class GroupGambit implements IGambit<GambitType.KeyValue> {
  type = GambitType.KeyValue;

  pattern(): string {
    return 'group:(.+)';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'group';

    return {
      [key]: matches[1].split(','),
    };
  }

  filterKey(): string {
    return 'group';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}group:${value}`;
  }

  suggestion() {
    return {
      key: app.translator.trans('core.lib.gambits.users.group.key', {}, true),
      hint: app.translator.trans('core.lib.gambits.users.group.hint', {}, true),
    };
  }
}
