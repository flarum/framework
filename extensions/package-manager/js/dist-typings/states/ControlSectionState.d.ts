import { UpdaterLoadingTypes } from '../components/Updater';
import { InstallerLoadingTypes } from '../components/Installer';
import { MajorUpdaterLoadingTypes } from '../components/MajorUpdater';
import { Extension } from 'flarum/admin/AdminApplication';
export declare type UpdatedPackage = {
    name: string;
    version: string;
    latest: string;
    'latest-minor': string | null;
    'latest-major': string | null;
    'latest-status': string;
    description: string;
};
export declare type ComposerUpdates = {
    installed: UpdatedPackage[];
};
export declare type LastUpdateCheck = {
    checkedAt: Date | null;
    updates: ComposerUpdates;
};
declare type UpdateType = 'major' | 'minor' | 'global';
declare type UpdateStatus = 'success' | 'failure' | null;
export declare type UpdateState = {
    ranAt: Date | null;
    status: UpdateStatus;
    limitedPackages: string[];
    incompatibleExtensions: string[];
};
export declare type LastUpdateRun = {
    [key in UpdateType]: UpdateState;
} & {
    limitedPackages: () => string[];
};
export declare type LoadingTypes = UpdaterLoadingTypes | InstallerLoadingTypes | MajorUpdaterLoadingTypes;
export declare type CoreUpdate = {
    package: UpdatedPackage;
    extension: Extension;
};
export default class ControlSectionState {
    loading: LoadingTypes;
    packageUpdates: Record<string, UpdatedPackage>;
    lastUpdateCheck: LastUpdateCheck;
    extensionUpdates: Extension[];
    coreUpdate: CoreUpdate | null;
    get lastUpdateRun(): LastUpdateRun;
    constructor();
    isLoading(name?: LoadingTypes): boolean;
    isLoadingOtherThan(name: LoadingTypes): boolean;
    setLoading(name: LoadingTypes): void;
    checkForUpdates(): void;
    updateCoreMinor(): void;
    updateExtension(extension: Extension): void;
    updateGlobally(): void;
    formatExtensionUpdates(lastUpdateCheck: LastUpdateCheck): Extension[];
    formatCoreUpdate(lastUpdateCheck: LastUpdateCheck): CoreUpdate | null;
}
export {};
