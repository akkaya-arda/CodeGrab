export interface OutlookAccount {
    id: number;
    email: string;
    accessTokenExpiresAt: Date;
    refreshTokenExpiresAt: Date;
    isActive: boolean;
    lastUsedAt: Date;
    fetchCount: number;
}
