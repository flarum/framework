import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';
export default class AuditLog extends Model {
    actorId: () => string;
    client: () => string;
    ipAddress: () => string | null;
    action: () => string;
    payload: () => {
        [key: string]: any;
    };
    createdAt: () => Date | null | undefined;
    actor: () => false | User;
    discussion: () => false | Discussion;
    newDiscussion: () => false | Discussion;
    post: () => false | Post;
    tag: () => any;
    user: () => false | User;
}
