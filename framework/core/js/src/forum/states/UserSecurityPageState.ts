import AccessToken from '../../common/models/AccessToken';

export default class UserSecurityPageState {
  protected tokens: AccessToken[] | null = null;
  public loadingTerminateSessions: boolean = false;
  public loadingGlobalLogout: boolean = false;

  public hasLoadedTokens(): boolean {
    return this.tokens !== null;
  }

  public getTokens(): AccessToken[] | null {
    return this.tokens;
  }

  public setTokens(tokens: AccessToken[]): void {
    this.tokens = tokens;
  }

  public pushToken(token: AccessToken): void {
    this.tokens?.push(token);
  }

  public removeToken(token: AccessToken): void {
    this.tokens = this.tokens!.filter((t) => t !== token);
  }

  public getSessionTokens(): AccessToken[] {
    return this.tokens?.filter((token) => token.isSessionToken()).sort((a, b) => (b.isCurrent() ? 1 : -1)) || [];
  }

  public getDeveloperTokens(): AccessToken[] | null {
    return this.tokens?.filter((token) => !token.isSessionToken()) || null;
  }

  /**
   * Look up session tokens other than the current one.
   */
  public getOtherSessionTokens(): AccessToken[] {
    return this.tokens?.filter((token) => token.isSessionToken() && !token.isCurrent()) || [];
  }

  public hasOtherActiveSessions(): boolean {
    return (this.getOtherSessionTokens() || []).length > 0;
  }

  public removeOtherSessionTokens() {
    this.tokens = this.tokens!.filter((token) => !token.isSessionToken() || token.isCurrent());
  }
}
