import 'flarum/common/models/Discussion';
import 'flarum/forum/components/SettingsPage';

declare module 'flarum/common/models/Discussion' {
  export default interface Discussion {
    subscription(): string;
  }
}

declare module 'flarum/forum/components/SettingsPage' {
  export default interface SettingsPage {
    followAfterReplyLoading: boolean;
    followAfterCreateLoading: boolean;
    notifyForAllPostsLoading: boolean;
  }
}
