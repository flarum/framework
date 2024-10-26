import 'flarum/common/models/Post';
import 'flarum/forum/ForumApplication';
import 'flarum/forum/components/Post';

import Flag from '../forum/models/Flag';
import FlagListState from '../forum/states/FlagListState';
import Mithril from 'mithril';

declare module 'flarum/common/models/Post' {
  export default interface Post {
    flags: () => false | (Flag | undefined)[];
    canFlag: () => boolean;
  }
}

declare module 'flarum/forum/ForumApplication' {
  export default interface ForumApplication {
    flags: FlagListState;
  }
}

declare module 'flarum/forum/components/Post' {
  export default interface Post {
    flagReason: (flag: Flag) => Mithril.Children;
  }
}
