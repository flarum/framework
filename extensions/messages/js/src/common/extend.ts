import DialogMessage from './models/DialogMessage';
import Dialog from './models/Dialog';
import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';

export default [
  new Extend.Store()
    .add('dialogs', Dialog) //
    .add('dialog-messages', DialogMessage), //

  new Extend.Model(User) //
    .attribute<boolean>('canSendAnyMessage'),
];
