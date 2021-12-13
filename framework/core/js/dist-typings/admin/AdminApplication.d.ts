import Application from '../common/Application';
import ExtensionData from './utils/ExtensionData';
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
export default class AdminApplication extends Application {
    extensionData: ExtensionData;
    extensionCategories: {
        feature: number;
        theme: number;
        language: number;
    };
    history: {
        canGoBack: () => boolean;
        getPrevious: () => void;
        backUrl: () => string;
        back: () => void;
    };
    /**
     * Settings are serialized to the admin dashboard as strings.
     * Additional encoding/decoding is possible, but must take
     * place on the client side.
     *
     * @inheritdoc
     */
    data: Application['data'] & {
        extensions: Record<string, Extension>;
        settings: Record<string, string>;
        modelStatistics: Record<string, {
            total: number;
        }>;
    };
    constructor();
    /**
     * @inheritdoc
     */
    mount(): void;
    getRequiredPermissions(permission: string): string[];
}
