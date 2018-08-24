import Model from '../Model';
import computed from '../utils/computed';

export default class Notification extends Model {}

Object.assign(Notification.prototype, {
  contentType: Model.attribute('contentType'),
  subjectId: Model.attribute('subjectId'),
  content: Model.attribute('content'),
  time: Model.attribute('time', Model.date),

  isRead: Model.attribute('isRead'),
  unreadCount: Model.attribute('unreadCount'),
  additionalUnreadCount: computed('unreadCount', unreadCount => Math.max(0, unreadCount - 1)),

  user: Model.hasOne('user'),
  fromUser: Model.hasOne('fromUser'),
  subject: Model.hasOne('subject')
});
