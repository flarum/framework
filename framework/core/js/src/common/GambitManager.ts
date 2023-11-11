import IGambit from './query/IGambit';
import AuthorGambit from './query/discussions/AuthorGambit';
import CreatedGambit from './query/discussions/CreatedGambit';
import HiddenGambit from './query/discussions/HiddenGambit';
import UnreadGambit from './query/discussions/UnreadGambit';
import EmailGambit from './query/users/EmailGambit';
import GroupGambit from './query/users/GroupGambit';

/**
 * The gambit registry. A map of resource types to gambit classes that
 * should be used to filter resources of that type. Gambits are automatically
 * converted to API filters when requesting resources. Gambits must be applied
 * on a filter object that has a `q` property containing the search query.
 */
export default class GambitManager {
  gambits: Record<string, Array<new () => IGambit>> = {
    discussions: [AuthorGambit, CreatedGambit, HiddenGambit, UnreadGambit],
    users: [EmailGambit, GroupGambit],
  };

  public apply(type: string, filter: Record<string, any>): Record<string, any> {
    const gambits = this.gambits[type] || [];

    if (gambits.length === 0) return filter;

    const bits: string[] = filter.q.split(' ');

    for (const gambitClass of gambits) {
      const gambit = new gambitClass();

      for (const bit of bits) {
        const pattern = `^(-?)${gambit.pattern()}$`;
        let matches = bit.match(pattern);

        if (matches) {
          const negate = matches[1] === '-';

          matches.splice(1, 1);

          Object.assign(filter, gambit.toFilter(matches, negate));

          filter.q = filter.q.replace(bit, '');
        }
      }
    }

    filter.q = filter.q.trim().replace(/\s+/g, ' ');

    return filter;
  }

  public from(type: string, q: string, filter: Record<string, any>): string {
    const gambits = this.gambits[type] || [];

    if (gambits.length === 0) return q;

    Object.keys(filter).forEach((key) => {
      for (const gambitClass of gambits) {
        const gambit = new gambitClass();
        const negate = key[0] === '-';

        if (negate) key = key.substring(1);

        if (gambit.filterKey() !== key) continue;

        q += ` ${gambit.fromFilter(filter[key], negate)}`;
      }
    });

    return q;
  }
}
