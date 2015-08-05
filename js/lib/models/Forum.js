import Model from 'flarum/Model';
import mixin from 'flarum/utils/mixin';

export default class Forum extends mixin(Model, {
  canStartDiscussion: Model.attribute('canStartDiscussion')
}) {
  apiEndpoint() {
    return '/forum';
  }
}
