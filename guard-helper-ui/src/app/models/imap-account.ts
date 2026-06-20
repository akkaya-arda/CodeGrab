export interface ImapAccount {
    id: number;
    email: string;
    host: string;
    port: number;
    encryption: string;
    isActive: boolean;
    lastUsedAt?: Date;
    fetchCount: number;
}
