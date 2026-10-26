import app from 'flarum/forum/app';
import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';
import type Dialog from '../common/models/Dialog';
import extendDeck from './extendDeck';

export default [
  ...commonExtend,

  new Extend.Routes() //
    .add('messages', '/messages', () => import('./components/MessagesPage'))
    .add('dialog', '/messages/dialog/:id', () => import('./components/MessagesPage'))
    .add('dialog.message', '/messages/dialog/:id/:near', () => import('./components/MessagesPage'))
    .helper('dialog', (dialog: Dialog, near?: number) => app.route(near ? 'dialog.message' : 'dialog', { id: dialog.id(), near: near })),

  // flarum/deck is an optional dependency, so it has loaded before this runs.
  ...('flarum-deck' in flarum.extensions ? extendDeck() : []),
];
