export type OperatingSystem = 'Mac' | 'iOS' | 'Windows' | 'Android' | 'Linux' | 'Unknown';

/**
 * Attempts to find the OS that the device is using.
 *
 * Not totally accurate, but should be good enough in most cases.
 *
 * @returns The device OS
 */
export default function getOperatingSystem(): OperatingSystem {
  // Adapted using content from
  // https://stackoverflow.com/a/19883965

  const userAgent = window.navigator.userAgent;
  const platform = window.navigator.platform;

  // Covers all **supported** Mac devices
  const macosPlatforms = ['MacIntel', 'darwin'];

  // All modern Windows browsers report "Win32" even on 64-bit builds
  const windowsPlatforms = ['Win32', 'Win64', 'Windows', 'WinCE'];

  // Covers the iOS devices
  const iosPlatforms = ['iPhone', 'iPad', 'iPod'];

  if (macosPlatforms.includes(platform)) {
    return 'Mac';
  } else if (iosPlatforms.includes(platform)) {
    return 'iOS';
  } else if (windowsPlatforms.includes(platform)) {
    return 'Windows';
  } else if (/Android/.test(userAgent)) {
    // Check for Android MUST come before Linux, as
    // the useragent includes Linux too
    //
    // We also can't use platform as that returns
    // Android on some devices, Linux on others,
    // the CPU arch on some more, and null on a
    // few...
    return 'Android';
  } else if (/Linux/.test(platform)) {
    // Linux distros usually include the distro
    // name in the platform value
    return 'Linux';
  }

  return 'Unknown';
}
