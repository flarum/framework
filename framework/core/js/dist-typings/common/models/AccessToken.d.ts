import Model from '../Model';
export default class AccessToken extends Model {
    token(): string | undefined;
    userId(): string;
    title(): string | null;
    type(): string;
    createdAt(): Date;
    lastActivityAt(): Date;
    lastIpAddress(): string;
    device(): string;
    isCurrent(): boolean;
    isSessionToken(): boolean;
}
