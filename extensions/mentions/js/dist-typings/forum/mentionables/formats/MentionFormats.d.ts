import type MentionFormat from './MentionFormat';
import MentionableModel from '../MentionableModel';
export default class MentionFormats {
    protected formats: MentionFormat[];
    get(symbol: string): MentionFormat | null;
    mentionable(type: string): MentionableModel | null;
    extend(format: new () => MentionFormat): void;
}
