import Model from 'flarum/common/Model';

class Flag extends Model {}

Object.assign(Flag.prototype, {
  type: Model.attribute('type'),
  reason: Model.attribute('reason'),
  reasonDetail: Model.attribute('reasonDetail'),
  createdAt: Model.attribute('createdAt', Model.transformDate),

  post: Model.hasOne('post'),
  user: Model.hasOne('user'),
});

export default Flag;
