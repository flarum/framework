import app from '../../app';
import IGambit, { GambitType } from '../IGambit';

export default class EmailGambit implements IGambit<GambitType.KeyValue> {
  type = GambitType.KeyValue;

  pattern(): string {
    return 'email:(.+)';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'email';

    return {
      [key]: matches[1],
    };
  }

  filterKey(): string {
    return 'email';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}email:${value}`;
  }

  suggestion() {
    return {
      key: app.translator.trans('core.lib.gambits.users.email.key', {}, true),
      hint: app.translator.trans('core.lib.gambits.users.email.hint', {}, true),
    };
  }
}
