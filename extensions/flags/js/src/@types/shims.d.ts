import type Flag from '../forum/models/Flag';
import type FlagListState from '../forum/states/FlagListState';
import type Mithril from 'mithril';
import type ItemList from 'flarum/common/utils/ItemList';

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
    dismissFlag: (body: any) => Promise<any>;
    flagActionItems: () => ItemList<Mithril.Children>;
    flagReason: (flag: Flag) => Mithril.Children;
  }
}
