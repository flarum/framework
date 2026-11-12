import { KeyValueGambit, type GambitValueOption } from '../IGambit';
export default class GroupGambit extends KeyValueGambit {
    predicates: boolean;
    key(): string;
    canonicalKey(): string;
    hint(): string;
    filterKey(): string;
    values(): (string | GambitValueOption)[];
}
