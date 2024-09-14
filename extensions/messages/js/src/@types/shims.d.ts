import type Dialog from '../common/models/Dialog';
import DialogListState from '../forum/states/DialogListState';

declare module 'flarum/forum/routes' {
  export interface ForumRoutes {
    dialog: (tag: Dialog) => string;
  }
}

declare module 'flarum/forum/ForumApplication' {
  export default interface ForumApplication {
    dialogs: DialogListState;
    dropdownDialogs: DialogListState;
  }
}

declare module 'flarum/forum/states/ComposerState' {
  export default interface ComposerState {
    composingMessageTo(dialog: Dialog): boolean;
  }
}
