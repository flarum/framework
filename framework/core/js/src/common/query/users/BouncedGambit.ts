import app from '../../app';
import { BooleanGambit } from '../IGambit';

export default class BouncedGambit extends BooleanGambit {
  key(): string {
    return app.translator.trans('core.lib.gambits.users.bounced.key', {}, true);
  }

  filterKey(): string {
    return 'bounced';
  }

  enabled(): boolean {
    // Only meaningful to admins, who can see bounce state and manage users.
    return !!app.session.user?.isAdmin();
  }
}
