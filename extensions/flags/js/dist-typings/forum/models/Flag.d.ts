import Model from 'flarum/common/Model';
export default class Flag extends Model {
    type(): any;
    reason(): any;
    reasonDetail(): any;
    createdAt(): any;
    post(): any;
    user(): any;
}
