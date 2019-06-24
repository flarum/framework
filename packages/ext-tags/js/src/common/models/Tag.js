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
  icon: Model.attribute('icon'),

  position: Model.attribute('position'),
  parent: Model.hasOne('parent'),
  defaultSort: Model.attribute('defaultSort'),
  isChild: Model.attribute('isChild'),
  isHidden: Model.attribute('isHidden'),

  discussionCount: Model.attribute('discussionCount'),
  lastPostedAt: Model.attribute('lastPostedAt', Model.transformDate),
  lastPostedDiscussion: Model.hasOne('lastPostedDiscussion'),

  isRestricted: Model.attribute('isRestricted'),
  canStartDiscussion: Model.attribute('canStartDiscussion'),
  canAddToDiscussion: Model.attribute('canAddToDiscussion'),

  isPrimary: computed('position', 'parent', (position, parent) => position !== null && parent === false)
}) {}
