import Model from 'flarum/common/Model';
export default class OAuthClient extends Model {
    name: () => string;
    redirectUris: () => string[];
    scopes: () => string[] | null;
    confidential: () => boolean;
    revoked: () => boolean;
    source: () => "admin" | "metadata_document" | "dynamic";
    plainSecret: () => string | null;
    createdAt: () => Date;
    updatedAt: () => Date | null;
}
