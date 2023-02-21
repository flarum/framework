import Extend from 'flarum/common/extenders';
import IndexPage from 'flarum/forum/components/IndexPage';
import Discussion from 'flarum/common/models/Discussion';

export default [
  new Extend.Routes() //
    .add('following', '/following', IndexPage),

  new Extend.Model(Discussion) //
    .attribute('subscription'),
];
