import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
import type Tag from 'ext:flarum/tags/common/models/Tag';
export default class AuditLog extends Model {
    actorId(): number | null;
    client(): string;
    ipAddress(): string | null;
    action(): string;
    payload(): {
        [key: string]: any;
    };
    createdAt(): Date | null | undefined;
    actor(): false | User;
    discussion(): false | Discussion;
    newDiscussion(): false | Discussion;
    post(): false | Post;
    tag(): false | Tag;
    user(): false | User;
}
