import 'flarum/forum/ForumApplication';
import 'flarum/common/models/User';
import 'flarum/forum/components/SettingsPage';

declare module 'flarum/forum/ForumApplication' {
  import ErasureRequestsListState from '../forum/states/ErasureRequestsListState';

  export default interface ForumApplication {
    erasureRequests: ErasureRequestsListState;
  }
}

declare module 'flarum/common/models/User' {
  import User from 'flarum/common/models/User';
  import ErasureRequest from '../../common/models/ErasureRequest';

  export default interface User {
    canModerateExports(): boolean;
    anonymized(): boolean;
    erasureRequest: ErasureRequest;
  }
}

declare module 'flarum/forum/components/SettingsPage' {
  import ItemList from 'flarum/common/utils/ItemList';
  import Mithril from 'mithril';

  export default interface SettingsPage {
    dataItems(): ItemList<Mithril.Children>;
  }
}
