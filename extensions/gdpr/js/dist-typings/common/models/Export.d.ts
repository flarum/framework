import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
export default class Export extends Model {
    file(): string;
    createdAt(): () => Date | null | undefined;
    destroysAt(): () => Date | null | undefined;
    user(): () => false | User;
    actor(): () => false | User;
}
