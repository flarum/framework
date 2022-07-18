import Model from '../Model';
export default class Group extends Model {
    static ADMINISTRATOR_ID: string;
    static GUEST_ID: string;
    static MEMBER_ID: string;
    nameSingular(): string;
    namePlural(): string;
    color(): string | null;
    icon(): string | null;
    isHidden(): boolean;
}
