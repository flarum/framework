import app from '../../app';
import { KeyValueGambit } from '../IGambit';

export default class CreatedGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('core.lib.gambits.discussions.created.key', {}, true);
  }

  hint(): string {
    return app.translator.trans('core.lib.gambits.discussions.created.hint', {}, true);
  }

  valuePattern(): string {
    return '(\\d{4}\\-\\d\\d\\-\\d\\d(?:\\.\\.(\\d{4}\\-\\d\\d\\-\\d\\d))?)';
  }

  filterKey(): string {
    return 'created';
  }
}
