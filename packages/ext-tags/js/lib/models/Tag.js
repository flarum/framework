import Model from 'flarum/Model';
import mixin from 'flarum/utils/mixin';
import computed from 'flarum/utils/computed';

export default class Tag extends mixin(Model, {
  name: Model.attribute('name'),
  slug: Model.attribute('slug'),
  description: Model.attribute('description'),

  color: Model.attribute('color'),
  backgroundUrl: Model.attribute('backgroundUrl'),
  backgroundMode: Model.attribute('backgroundMode'),
  iconUrl: Model.attribute('iconUrl'),

  position: Model.attribute('position'),
  parent: Model.hasOne('parent'),
  defaultSort: Model.attribute('defaultSort'),
  isChild: Model.attribute('isChild'),

  discussionsCount: Model.attribute('discussionsCount'),
  lastTime: Model.attribute('lastTime', Model.transformDate),
  lastDiscussion: Model.hasOne('lastDiscussion'),

  isRestricted: Model.attribute('isRestricted'),
  canStartDiscussion: Model.attribute('canStartDiscussion'),

  isPrimary: computed('position', 'parent', (position, parent) => position !== null && parent === false)
}) {}
