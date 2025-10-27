import Model from 'flarum/common/Model';
import type { Extension } from 'flarum/admin/AdminApplication';
export default class ExternalExtension extends Model {
    extensionId: any;
    name: any;
    title: any;
    description: any;
    iconUrl: any;
    icon: any;
    highestVersion: any;
    httpUri: any;
    discussUri: any;
    vendor: any;
    isPremium: any;
    isLocale: any;
    locale: any;
    latestFlarumVersionSupported: any;
    downloads: any;
    isSupported: any;
    readonly installed = false;
    isProductionReady(): boolean;
    toLocalExtension(): Extension;
}
