import { BooleanGambit } from 'flarum/common/query/IGambit';
import app from 'flarum/common/app';

export default class LockedGambit extends BooleanGambit {
  key(): string {
    return app.translator.trans('flarum-lock.lib.gambits.discussions.locked.key', {}, true);
  }

  filterKey(): string {
    return 'locked';
  }
}
