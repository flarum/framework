import app from '../../app';
import { KeyValueGambit } from '../IGambit';

export default class EmailGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('core.lib.gambits.users.email.key', {}, true);
  }

  hint(): string {
    return app.translator.trans('core.lib.gambits.users.email.hint', {}, true);
  }

  filterKey(): string {
    return 'email';
  }

  enabled(): boolean {
    return !!(app.session.user && app.forum.attribute<boolean>('canEditUserCredentials'));
  }
}
