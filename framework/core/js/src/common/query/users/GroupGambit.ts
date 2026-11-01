import app from '../../app';
import { KeyValueGambit, type GambitValueOption } from '../IGambit';
import type Group from '../../models/Group';

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

  values(): (string | GambitValueOption)[] {
    return (app.store.all<Group>('groups') || [])
      .map((group) => {
        const name = group.nameSingular() || group.namePlural() || group.id()!;
        return name ? { value: name } : null;
      })
      .filter((opt): opt is GambitValueOption => !!opt);
  }
}
