import Model from 'flarum/common/Model';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
export default class Flag extends Model {
    type(): string;
    reason(): string | null;
    reasonDetail(): string | null;
    createdAt(): Date | null | undefined;
    post(): false | Post;
    user(): false | User | null;
}
