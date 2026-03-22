import MentionFormat from './MentionFormat';
import MentionableModel from '../MentionableModel';
export default class HashMentionFormat extends MentionFormat {
    mentionables: (new (...args: any[]) => MentionableModel)[];
    protected extendable: boolean;
    trigger(): string;
    queryFromTyped(typed: string): string | null;
    format(slug: string): string;
}
