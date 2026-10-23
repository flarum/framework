import Model from 'flarum/common/Model';
import type { Extension } from 'flarum/admin/AdminApplication';
export default class ExternalExtension extends Model {
    extensionId: () => string;
    name: () => string;
    title: () => string;
    description: () => string;
    iconUrl: () => string;
    icon: () => {
        [key: string]: string;
        name: string;
    };
    highestVersion: () => string;
    httpUri: () => string;
    vendor: () => string;
    isLocale: () => boolean;
    downloads: () => number;
    abandoned: () => boolean;
    readonly installed = false;
    toLocalExtension(): Extension;
}
