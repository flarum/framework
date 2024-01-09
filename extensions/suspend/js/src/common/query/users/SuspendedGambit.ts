import app from 'flarum/common/app';
import { BooleanGambit } from 'flarum/common/query/IGambit';

export default class SuspendedGambit extends BooleanGambit {
  key(): string {
    return app.translator.trans('flarum-suspend.lib.gambits.users.suspended.key', {}, true);
  }

  filterKey(): string {
    return 'suspended';
  }

  enabled(): boolean {
    return !!app.session.user && app.forum.attribute<boolean>('canSuspendUsers');
  }
}
