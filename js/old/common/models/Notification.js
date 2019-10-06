import Model from '../Model';

export default class Notification extends Model {}

Object.assign(Notification.prototype, {
  contentType: Model.attribute('contentType'),
  content: Model.attribute('content'),
  createdAt: Model.attribute('createdAt', Model.transformDate),

  isRead: Model.attribute('isRead'),

  user: Model.hasOne('user'),
  fromUser: Model.hasOne('fromUser'),
  subject: Model.hasOne('subject')
});
