import MentionFormat from './MentionFormat';
import type MentionableModel from '../MentionableModel';
export default class AtMentionFormat extends MentionFormat {
    mentionables: (new (...args: any[]) => MentionableModel)[];
    protected extendable: boolean;
    trigger(): string;
    queryFromTyped(typed: string): string | null;
    format(name: string, char?: string | null, id?: string | null): string;
}
