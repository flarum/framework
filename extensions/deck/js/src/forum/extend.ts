import Extend from 'flarum/common/extenders';

export default [
  new Extend.Routes() //
    .add('deck', '/deck', () => import('./components/DeckPage')),
];
