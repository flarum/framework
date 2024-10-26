import 'flarum/forum/routes';
import 'flarum/common/Application';
import 'flarum/common/models/Discussion';
import 'flarum/forum/components/IndexPage';
import 'flarum/admin/components/PermissionGrid';

import type Tag from '../common/models/Tag';
import type TagListState from '../common/states/TagListState';

declare module 'flarum/forum/routes' {
  export interface ForumRoutes {
    tag: (tag: Tag) => string;
  }
}

declare module 'flarum/common/Application' {
  export default interface Application {
    tagList: TagListState;
  }
}

declare module 'flarum/common/models/Discussion' {
  export default interface Discussion {
    tags: () => false | (Tag | undefined)[];
    canTag: () => boolean | undefined;
  }
}

declare module 'flarum/forum/components/IndexPage' {
  export default interface IndexPage {
    currentActiveTag?: Tag;
    currentTagLoading?: boolean;
    currentTag: () => Tag | undefined;
  }
}

declare module 'flarum/admin/components/PermissionGrid' {
  export interface PermissionConfig {
    tagScoped?: boolean;
  }
  export default interface PermissionGrid {
    loading?: boolean;
  }
}
