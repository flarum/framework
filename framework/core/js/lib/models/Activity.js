import Model from 'flarum/Model';
import mixin from 'flarum/utils/mixin';

export default class Activity extends mixin(Model, {
  contentType: Model.attribute('contentType'),
  content: Model.attribute('content'),
  time: Model.attribute('time', Model.transformDate),

  user: Model.hasOne('user'),
  subject: Model.hasOne('subject')
}) {}
