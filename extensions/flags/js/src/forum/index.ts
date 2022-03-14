import app from 'flarum/forum/app';
import Model from 'flarum/common/Model';

import Flag from './models/Flag';
import FlagsPage from './components/FlagsPage';
import FlagListState from './states/FlagListState';
import addFlagControl from './addFlagControl';
import addFlagsDropdown from './addFlagsDropdown';
import addFlagsToPosts from './addFlagsToPosts';

app.initializers.add('flarum-flags', () => {
  Post.prototype.flags = Model.hasMany<Flag>('flags');
  Post.prototype.canFlag = Model.attribute<boolean>('canFlag');

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
import Post from 'flarum/common/models/Post';

Object.assign(compat, flagsCompat);
