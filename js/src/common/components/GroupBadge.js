import Badge from './Badge';

export default class GroupBadge extends Badge {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    if (attrs.group) {
      attrs.icon = attrs.group.icon();
      attrs.style = { backgroundColor: attrs.group.color() };
      attrs.label = typeof attrs.label === 'undefined' ? attrs.group.nameSingular() : attrs.label;
      attrs.type = 'group--' + attrs.group.id();

      delete attrs.group;
    }
  }
}
