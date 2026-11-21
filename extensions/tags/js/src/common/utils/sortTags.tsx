import Tag from '../models/Tag';

export default function sortTags(tags: Tag[]) {
  return tags.slice(0).sort((a, b) => {
    // If they're both secondary tags, sort them by their discussions count,
    // descending.
    if (a.isSecondary() && b.isSecondary()) return b.discussionCount() - a.discussionCount();

    // If just one is a secondary tag, then the primary tag should
    // come first.
    if (b.isSecondary()) return -1;
    if (a.isSecondary()) return 1;

    const aPos = a.position() ?? 0;
    const bPos = b.position() ?? 0;

    // If we've made it this far, we know they're both primary tags. So we'll
    // need to see if they have parents.
    const aParent = a.parent();
    const bParent = b.parent();

    // If they both have the same parent, then their positions are local,
    // so we can compare them directly.
    if (aParent === bParent) return aPos - bPos;
    // If they are both child tags, then we will compare the positions of their
    // parents.
    else if (aParent && bParent) return aParent.position()! - bParent.position()!;
    // If we are comparing a child tag with its parent, then we let the parent
    // come first. If we are comparing an unrelated parent/child, then we
    // compare both of the parents.
    else if (aParent) return aParent === b ? 1 : aParent.position()! - bPos;
    else if (bParent) return bParent === a ? -1 : aPos - bParent.position()!;

    return 0;
  });
}
