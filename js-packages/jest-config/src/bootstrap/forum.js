import app from '@flarum/core/src/forum/app';
import ForumApplication from '@flarum/core/src/forum/ForumApplication';
import bootstrap from './common.js';

export default function bootstrapForum(payload = {}) {
  return bootstrap(ForumApplication, app, payload);
}
