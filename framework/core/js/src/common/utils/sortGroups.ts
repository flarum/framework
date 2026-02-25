import type Group from '../models/Group';

export default function sortGroups(groups: Group[]) {
  return groups.slice().sort((a, b) => {
    const aPos = a.position();
    const bPos = b.position();

    if (aPos === null && bPos === null) return 0;
    if (aPos === null) return 1;
    if (bPos === null) return -1;

    return aPos - bPos;
  });
}
