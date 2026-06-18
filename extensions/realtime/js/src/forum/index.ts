import app from 'flarum/forum/app';
import Application from './extend/Application';
import Discussion from './extend/Discussion';
import DiscussionList from './extend/DiscussionList';
import Tags from './extend/Tags';
import User from './extend/User';

import RealtimeExtend from './extenders/Realtime';
import RealtimeState from './RealtimeState';

// Manually register these for other extensions to consume via ext: imports,
// since the autoExportLoader cannot match ES class / instance default exports.
flarum.reg.add('flarum-realtime', 'forum/extenders/Realtime', RealtimeExtend);
flarum.reg.add('flarum-realtime', 'forum/RealtimeState', RealtimeState);

export { default as extend } from './extend';

app.initializers.add('flarum-realtime', () => {
  Application();
  Discussion();
  DiscussionList();
  Tags();
  User();
});
