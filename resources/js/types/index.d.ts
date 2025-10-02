import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id?: number;
    full_name?: string;
    username?: string;
    email?: string;
    avatar?: string | null;
    points?: number;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string | null;
    title?: string | null;
    bio?: string | null;
    linkedin_url?: string | null;
    website_url?: string | null;
    is_public?: boolean;
}

export type BreadcrumbItemType = BreadcrumbItem;
