import type IGambit from './query/IGambit';
/**
 * The gambit registry. A map of resource types to gambit classes that
 * should be used to filter resources of that type. Gambits are automatically
 * converted to API filters when requesting resources. Gambits must be applied
 * on a filter object that has a `q` property containing the search query.
 */
export default class GambitManager {
    gambits: Record<string, Array<new () => IGambit>>;
    apply(type: string, filter: Record<string, any>): Record<string, any>;
    match(type: string, query: string, onmatch: (gambit: IGambit, matches: string[], negate: boolean, bit: string) => void): string;
    from(type: string, q: string, filter: Record<string, any>): string;
    for(type: string): Array<IGambit>;
}
