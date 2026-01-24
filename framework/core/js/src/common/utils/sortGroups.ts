import type Group from '../models/Group';

export default function sortGroups(groups: Group[]) {
  return groups.slice().sort((a, b) => a.position() - b.position());
}
