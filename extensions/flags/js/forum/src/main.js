import app from 'flarum/app';
import Model from 'flarum/Model';

import Flag from 'flags/models/Flag';
import FlagsPage from 'flags/components/FlagsPage';
import addFlagControl from 'flags/addFlagControl';
import addFlagsDropdown from 'flags/addFlagsDropdown';
import addFlagsToPosts from 'flags/addFlagsToPosts';

app.initializers.add('flags', () => {
  app.store.models.posts.prototype.flags = Model.hasMany('flags');
  app.store.models.posts.prototype.canFlag = Model.attribute('canFlag');

  app.store.models.flags = Flag;

  app.routes.flags = {path: '/flags', component: <FlagsPage/>};

  addFlagControl();
  addFlagsDropdown();
  addFlagsToPosts();
});
