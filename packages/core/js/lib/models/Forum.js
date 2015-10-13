import Model from 'flarum/Model';
import mixin from 'flarum/utils/mixin';

export default class Forum extends Model {
  apiEndpoint() {
    return '/forum';
  }
}
