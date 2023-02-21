import AccessToken from '../../common/models/AccessToken';
export default class UserSecurityPageState {
    protected tokens: AccessToken[] | null;
    loadingTerminateSessions: boolean;
    loadingGlobalLogout: boolean;
    hasLoadedTokens(): boolean;
    getTokens(): AccessToken[] | null;
    setTokens(tokens: AccessToken[]): void;
    pushToken(token: AccessToken): void;
    removeToken(token: AccessToken): void;
    getSessionTokens(): AccessToken[];
    getDeveloperTokens(): AccessToken[] | null;
    /**
     * Look up session tokens other than the current one.
     */
    getOtherSessionTokens(): AccessToken[];
    hasOtherActiveSessions(): boolean;
    removeOtherSessionTokens(): void;
}
