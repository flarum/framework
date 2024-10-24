import { BooleanGambit } from 'flarum/common/query/IGambit';
import app from 'flarum/common/app';

export default class StickyGambit extends BooleanGambit {
  key(): string {
    return app.translator.trans('flarum-sticky.lib.gambits.discussions.sticky.key', {}, true);
  }

  filterKey(): string {
    return 'sticky';
  }
}
