import type IGambit from './query/IGambit';
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
    filter.q = this.match(type, filter.q, (gambit, matches, negate) => {
      const additions = gambit.toFilter(matches, negate);

      Object.keys(additions).forEach((key) => {
        if (key in filter && gambit.predicates && Array.isArray(additions[key])) {
          filter[key] = filter[key].concat(additions[key]);
        } else {
          filter[key] = additions[key];
        }
      });
    });

    return filter;
  }

  public match(type: string, query: string, onmatch: (gambit: IGambit, matches: string[], negate: boolean, bit: string) => void): string {
    const gambits = this.for(type).filter((gambit) => gambit.enabled());

    if (gambits.length === 0) return query;

    const bits: string[] = query.split(' ');

    for (const gambit of gambits) {
      for (const bit of bits) {
        const pattern = `^(-?)${gambit.pattern()}$`;
        let matches = bit.match(pattern);

        if (matches) {
          const negate = matches[1] === '-';

          matches.splice(1, 1);

          onmatch(gambit, matches, negate, bit);

          query = query.replace(bit, '');
        }
      }
    }

    query = query.trim().replace(/\s+/g, ' ');

    return query;
  }

  public from(type: string, q: string, filter: Record<string, any>): string {
    const gambits = this.for(type);

    if (gambits.length === 0) return q;

    Object.keys(filter).forEach((key) => {
      for (const gambit of gambits) {
        const negate = key[0] === '-';

        if (negate) key = key.substring(1);

        if (gambit.filterKey() !== key) continue;

        q += ` ${gambit.fromFilter(filter[key], negate)}`;
      }
    });

    return q;
  }

  for(type: string): Array<IGambit> {
    return (this.gambits[type] || []).map((gambitClass) => new gambitClass());
  }
}
