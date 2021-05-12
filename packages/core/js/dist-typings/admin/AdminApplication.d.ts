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
    getRequiredPermissions(permission: any): string[];
}
import Application from "../common/Application";
import ExtensionData from "./utils/ExtensionData";
