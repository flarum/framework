import Model from '../Model';
export default class Group extends Model {
    static ADMINISTRATOR_ID: string;
    static GUEST_ID: string;
    static MEMBER_ID: string;
    nameSingular(): string;
    namePlural(): string;
    color(): string | null;
    /**
     * The Font Awesome icon classes, with the classes that show the uploaded
     * image in their place when the group has one. The stored classes alone are
     * `attribute('icon')`.
     */
    icon(): string | null;
    iconImageUrl(): string | null;
    iconImageSrcset(): string | null;
    isHidden(): boolean;
    position(): number;
}
