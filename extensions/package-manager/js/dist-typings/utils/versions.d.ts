export declare enum VersionStability {
    Stable = "stable",
    Alpha = "alpha",
    Beta = "beta",
    RC = "rc",
    Dev = "dev"
}
export declare function isProductionReady(version: string): boolean;
export declare function stability(version: string): VersionStability;
