import IGambit from '../IGambit';

export default class HiddenGambit implements IGambit {
  public pattern(): string {
    return 'is:hidden';
  }

  public toFilter(_matches: string[], negate: boolean): Record<string, any> {
    const key = (negate ? '-' : '') + 'hidden';

    return {
      [key]: true,
    };
  }
}
