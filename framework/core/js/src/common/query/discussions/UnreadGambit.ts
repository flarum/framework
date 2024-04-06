import app from '../../app';
import { BooleanGambit } from '../IGambit';

export default class UnreadGambit extends BooleanGambit {
  key(): string {
    return app.translator.trans('core.lib.gambits.discussions.unread.key', {}, true);
  }

  filterKey(): string {
    return 'unread';
  }

  enabled(): boolean {
    return !!app.session.user;
  }
}
