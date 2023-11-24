import app from '../../app';
import IGambit, { GambitType } from '../IGambit';

export default class HiddenGambit implements IGambit<GambitType.Grouped> {
  type = GambitType.Grouped;

  public pattern(): string {
    return 'is:hidden';
  }

  public toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'hidden';

    return {
      [key]: true,
    };
  }

  filterKey(): string {
    return 'hidden';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}is:hidden`;
  }

  suggestion() {
    return {
      group: 'is',
      key: app.translator.trans('core.lib.gambits.discussions.hidden.key', {}, true),
    };
  }
}
