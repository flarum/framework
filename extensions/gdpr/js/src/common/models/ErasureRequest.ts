import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';

export default class ErasureRequest extends Model {
  status() {
    return Model.attribute<string>('status').call(this);
  }

  reason() {
    return Model.attribute<string>('reason').call(this);
  }

  createdAt() {
    return Model.attribute('createdAt', Model.transformDate).call(this);
  }

  userConfirmedAt() {
    return Model.attribute('userConfirmedAt', Model.transformDate).call(this);
  }

  processedAt() {
    return Model.attribute('processedAt', Model.transformDate).call(this);
  }

  processorComment() {
    return Model.attribute<string>('processorComment').call(this);
  }

  processedMode() {
    return Model.attribute<string>('processedMode').call(this);
  }

  user() {
    return Model.hasOne<User>('user').call(this);
  }

  processedBy() {
    return Model.hasOne<User>('processedBy').call(this);
  }
}
