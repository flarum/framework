import app from '../../app';
import { BooleanGambit } from '../IGambit';

export default class HiddenGambit extends BooleanGambit {
  key(): string {
    return app.translator.trans('core.lib.gambits.discussions.hidden.key', {}, true);
  }

  filterKey(): string {
    return 'hidden';
  }

  enabled(): boolean {
    return !!app.session.user;
  }
}
