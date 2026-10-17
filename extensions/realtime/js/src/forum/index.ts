import app from 'flarum/forum/app';
import Application from './extend/Application';
import Discussion from './extend/Discussion';
import DiscussionList from './extend/DiscussionList';
import Tags from './extend/Tags';
import User from './extend/User';
import Visibility from './extend/Visibility';

// Registers itself, for other extensions to import with ext:.
import './extenders/Realtime';
import RealtimeState from './RealtimeState';

// The autoExportLoader leaves out modules at the root of forum/, so register
// this one by hand for other extensions to import with ext:.
flarum.reg.add('flarum-realtime', 'forum/RealtimeState', RealtimeState);

export { default as extend } from './extend';

app.initializers.add('flarum-realtime', () => {
  Application();
  Discussion();
  DiscussionList();
  Tags();
  User();
  Visibility();
});
