import Application from '../common/Application';
import ExtensionData from './utils/ExtensionData';
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
        backUrl: () => any;
        back: () => void;
    };
    constructor();
    /**
     * @inheritdoc
     */
    mount(): void;
    getRequiredPermissions(permission: any): string[];
}
