import Badge, { IBadgeAttrs } from './Badge';
import Group from '../models/Group';

export interface IGroupAttrs extends IBadgeAttrs {
  group?: Group;
}

export default class GroupBadge<CustomAttrs extends IGroupAttrs = IGroupAttrs> extends Badge<CustomAttrs> {
  static initAttrs(attrs: IGroupAttrs): void {
    super.initAttrs(attrs);

    if (attrs.group) {
      attrs.icon = attrs.group.icon() || '';
      attrs.color = attrs.group.color() || '';
      attrs.label = typeof attrs.label === 'undefined' ? attrs.group.nameSingular() : attrs.label;
      attrs.type = 'group--' + attrs.group.id();

      delete attrs.group;
    }
  }
}
