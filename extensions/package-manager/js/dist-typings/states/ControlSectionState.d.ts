import { UpdaterLoadingTypes } from '../components/Updater';
import { InstallerLoadingTypes } from '../components/Installer';
import { MajorUpdaterLoadingTypes } from '../components/MajorUpdater';
import { Extension } from 'flarum/admin/AdminApplication';
export type UpdatedPackage = {
    name: string;
    version: string;
    latest: string;
    'latest-minor': string | null;
    'latest-major': string | null;
    'latest-status': string;
    'required-as': string;
    'direct-dependency': boolean;
    description: string;
};
export type ComposerUpdates = {
    installed: UpdatedPackage[];
};
export type LastUpdateCheck = {
    checkedAt: Date | null;
    updates: ComposerUpdates;
};
type UpdateType = 'major' | 'minor' | 'global';
type UpdateStatus = 'success' | 'failure' | null;
export type UpdateState = {
    ranAt: Date | null;
    status: UpdateStatus;
    limitedPackages: string[];
    incompatibleExtensions: string[];
};
export type LastUpdateRun = {
    [key in UpdateType]: UpdateState;
} & {
    limitedPackages: () => string[];
};
export type LoadingTypes = UpdaterLoadingTypes | InstallerLoadingTypes | MajorUpdaterLoadingTypes | 'queued-action';
export type CoreUpdate = {
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
    hasOperationRunning(): boolean;
    setLoading(name: LoadingTypes): void;
    requirePackage(data: any): void;
    checkForUpdates(): void;
    updateCoreMinor(): void;
    updateExtension(extension: Extension, updateMode: 'soft' | 'hard'): void;
    updateGlobally(): void;
    formatExtensionUpdates(lastUpdateCheck: LastUpdateCheck): Extension[];
    formatCoreUpdate(lastUpdateCheck: LastUpdateCheck): CoreUpdate | null;
    majorUpdate({ dryRun }: {
        dryRun: boolean;
    }): void;
}
export {};
