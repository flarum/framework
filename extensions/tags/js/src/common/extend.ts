import Extend from 'flarum/common/extenders';
import Tag from './models/Tag';

export default [
  new Extend.Store() //
    .add('tags', Tag),
];
