import app from '../../app';
import { KeyValueGambit } from '../IGambit';

export default class GroupGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('core.lib.gambits.users.group.key', {}, true);
  }

  canonicalKey(): string {
    return 'group';
  }

  hint(): string {
    return app.translator.trans('core.lib.gambits.users.group.hint', {}, true);
  }

  filterKey(): string {
    return 'group';
  }
}
