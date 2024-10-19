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
    discussUri: () => string;
    vendor: () => string;
    isPremium: () => boolean;
    isLocale: () => boolean;
    locale: () => string;
    latestFlarumVersionSupported: () => string;
    downloads: () => number;
    readonly installed = false;
    isSupported(): boolean;
    isProductionReady(): boolean;
    toLocalExtension(): Extension;
}
