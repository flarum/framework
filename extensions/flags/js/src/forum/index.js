import app from 'flarum/app';
import Model from 'flarum/Model';

import Flag from './models/Flag';
import FlagsPage from './components/FlagsPage';
import FlagListState from './states/FlagListState';
import addFlagControl from './addFlagControl';
import addFlagsDropdown from './addFlagsDropdown';
import addFlagsToPosts from './addFlagsToPosts';

app.initializers.add('flarum-flags', () => {
  app.store.models.posts.prototype.flags = Model.hasMany('flags');
  app.store.models.posts.prototype.canFlag = Model.attribute('canFlag');

  app.store.models.flags = Flag;

  app.routes.flags = { path: '/flags', component: FlagsPage };

  app.flags = new FlagListState(app);

  addFlagControl();
  addFlagsDropdown();
  addFlagsToPosts();
});

// Expose compat API
import flagsCompat from './compat';
import { compat } from '@flarum/core/forum';

Object.assign(compat, flagsCompat);
