import app from '../../app';
import IGambit, { GambitType } from '../IGambit';

export default class AuthorGambit implements IGambit<GambitType.KeyValue> {
  type = GambitType.KeyValue;

  public pattern(): string {
    return 'author:(.+)';
  }

  public toFilter(matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'author';

    return {
      [key]: matches[1].split(','),
    };
  }

  filterKey(): string {
    return 'author';
  }

  fromFilter(value: string, negate: boolean): string {
    return `${negate ? '-' : ''}author:${value}`;
  }

  suggestion() {
    return {
      key: app.translator.trans('core.lib.gambits.discussions.author.key', {}, true),
      hint: app.translator.trans('core.lib.gambits.discussions.author.hint', {}, true),
    };
  }
}
