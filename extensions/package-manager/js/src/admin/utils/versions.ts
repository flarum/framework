export enum VersionStability {
  Stable = 'stable',
  Alpha = 'alpha',
  Beta = 'beta',
  RC = 'rc',
  Dev = 'dev',
}

export function isProductionReady(version: string): boolean {
  return [VersionStability.Stable].includes(stability(version));
}

export function stability(version: string): VersionStability {
  const split = version.split('-');

  if (split.length === 1) {
    return VersionStability.Stable;
  }

  const stab = split[1].split('.')[0].toLowerCase();

  switch (stab) {
    case 'alpha':
      return VersionStability.Alpha;
    case 'beta':
      return VersionStability.Beta;
    case 'rc':
      return VersionStability.RC;
    default:
      return VersionStability.Dev;
  }
}
