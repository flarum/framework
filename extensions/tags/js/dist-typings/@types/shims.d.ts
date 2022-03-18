import type Tag from "../common/models/Tag";
import type TagListState from "../forum/states/TagListState";

declare module 'flarum/forum/routes' {
  export interface ForumRoutes {
    tag: (tag: Tag) => string;
  }
}

declare module 'flarum/forum/ForumApplication' {
  export default interface ForumApplication {
    tagList: TagListState;
  }
}

declare module 'flarum/common/models/Discussion' {
  export default interface Discussion {
    tags: () => false | (Tag | undefined)[];
    canTag: () => boolean | undefined;
  }
}
