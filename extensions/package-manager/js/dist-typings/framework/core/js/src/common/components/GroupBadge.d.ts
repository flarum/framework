import Badge, { IBadgeAttrs } from './Badge';
import Group from '../models/Group';
export interface IGroupAttrs extends IBadgeAttrs {
    group?: Group;
}
export default class GroupBadge<CustomAttrs extends IGroupAttrs = IGroupAttrs> extends Badge<CustomAttrs> {
    static initAttrs(attrs: IGroupAttrs): void;
}
