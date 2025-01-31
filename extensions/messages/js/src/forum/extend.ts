import app from 'flarum/forum/app';
import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';
import type Dialog from '../common/models/Dialog';

export default [
  ...commonExtend,

  new Extend.Routes() //
    .add('messages', '/messages', () => import('./components/MessagesPage'))
    .add('dialog', '/messages/dialog/:id', () => import('./components/MessagesPage'))
    .add('dialog', '/messages/dialog/:id/:near', () => import('./components/MessagesPage'))
    .helper('dialog', (dialog: Dialog, near?: number) => app.route('dialog', { id: dialog.id(), near: near })),
];
