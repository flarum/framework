import { AdminRoutes } from './routes';
import Application, { ApplicationData } from '../common/Application';
import ExtensionData from './utils/ExtensionData';
import IHistory from '../common/IHistory';
export declare type Extension = {
    id: string;
    version: string;
    description?: string;
    icon?: {
        name: string;
    };
    links: {
        authors?: {
            name?: string;
            link?: string;
        }[];
        discuss?: string;
        documentation?: string;
        support?: string;
        website?: string;
        donate?: string;
        source?: string;
    };
    extra: {
        'flarum-extension': {
            title: string;
        };
    };
};
export interface AdminApplicationData extends ApplicationData {
    extensions: Record<string, Extension>;
    settings: Record<string, string>;
    modelStatistics: Record<string, {
        total: number;
    }>;
}
export default class AdminApplication extends Application {
    extensionData: ExtensionData;
    extensionCategories: {
        feature: number;
        theme: number;
        language: number;
    };
    history: IHistory;
    /**
     * Settings are serialized to the admin dashboard as strings.
     * Additional encoding/decoding is possible, but must take
     * place on the client side.
     *
     * @inheritdoc
     */
    data: AdminApplicationData;
    route: typeof Application.prototype.route & AdminRoutes;
    constructor();
    /**
     * @inheritdoc
     */
    mount(): void;
    getRequiredPermissions(permission: string): string[];
}
