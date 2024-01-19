import Extend from 'flarum/common/extenders';
import Tag from './models/Tag';
import TagGambit from './query/discussions/TagGambit';

export default [
  new Extend.Store() //
    .add('tags', Tag),

  new Extend.Search() //
    .gambit('discussions', TagGambit),
];
