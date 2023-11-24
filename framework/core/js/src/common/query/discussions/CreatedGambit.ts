import app from '../../app';
import IGambit, { GambitType } from '../IGambit';

export default class CreatedGambit implements IGambit<GambitType.KeyValue> {
  type = GambitType.KeyValue;

  pattern(): string {
    return 'created:(\\d{4}\\-\\d\\d\\-\\d\\d(?:\\.\\.(\\d{4}\\-\\d\\d\\-\\d\\d))?)';
  }

  toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'created';

    return {
      [key]: matches[1],
    };
  }

  filterKey(): string {
    return 'created';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}created:${value}`;
  }

  suggestion() {
    return {
      key: app.translator.trans('core.lib.gambits.discussions.created.key', {}, true),
      hint: app.translator.trans('core.lib.gambits.discussions.created.hint', {}, true),
    };
  }
}
