import Extend from 'flarum/common/extenders';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
import FlagsPage from './components/FlagsPage';
import Flag from './models/Flag';
import extendDeck from './extendDeck';

export default [
  new Extend.Routes() //
    .add('flags', '/flags', FlagsPage),

  new Extend.Store() //
    .add('flags', Flag),

  new Extend.Model(Post) //
    .hasMany<Flag>('flags')
    .attribute<boolean>('canFlag'),

  new Extend.Model(User) //
    .hasMany<Flag>('flags')
    .attribute<boolean>('canFlagUser')
    .attribute<boolean>('canViewUserFlags'),

  // flarum/deck is an optional dependency, so it has loaded before this runs.
  ...('flarum-deck' in flarum.extensions ? extendDeck() : []),
];
