import type Discussion from 'flarum/common/models/Discussion';
import type Model from 'flarum/common/Model';
import type Post from 'flarum/common/models/Post';
import type { DeckRealtimeEvent } from './DeckColumnType';
/** Event names as flarum/realtime and the bundled extensions broadcast them. */
export declare const STARTED = "Flarum\\Discussion\\Event\\Started";
export declare const POSTED = "Flarum\\Post\\Event\\Posted";
export declare const REVISED = "revisedEvent";
export declare const LIKED = "likesMutation";
export declare const RENAMED = "discussionRenamed";
export declare const TAGGED = "taggedEvent";
export declare const NOTIFICATION = "notification";
export declare const POST_HIDDEN = "postHidden";
export declare const POST_RESTORED = "postRestored";
export declare const DISCUSSION_HIDDEN = "discussionHidden";
export declare const DISCUSSION_RESTORED = "discussionRestored";
/** Something the member could see has been hidden or deleted: `{ data: { type, id } }` only. */
export declare const REMOVED = "removed";
/**
 * Events about a post. Realtime sends these as the post's *discussion*, with
 * the post itself appended last to `included`.
 */
export declare const POST_EVENTS: string[];
/** The post an event is about: for post events, the one appended last to `included`. */
export declare function subjectPost(name: string, payload: any): Post | null;
/** A to-one relationship's id straight from the model's data, without needing the related model loaded. */
export declare function relatedId(model: Model, relationship: string): string | null;
export declare function postOf(event: DeckRealtimeEvent): Post | null;
export declare function discussionOf(event: DeckRealtimeEvent): Discussion | null;
