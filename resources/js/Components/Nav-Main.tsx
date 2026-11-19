import { Link, usePage } from '@inertiajs/react';

import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/Components/Ui/Sidebar';

type NavItem = {
    title: string;
    url: string;
    icon?: React.ElementType;
};

const isCurrentPath = (currentPath: string, href: string): boolean =>
    href === '/dashboard'
        ? currentPath === '/dashboard'
        : currentPath === href || currentPath.startsWith(`${href}/`);

export function NavMain({ items }: { items: NavItem[] }) {
    const { url } = usePage();
    const currentPath = url.split('?')[0];

    return (
        <SidebarGroup>
            <SidebarGroupContent>
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                tooltip={item.title}
                                isActive={isCurrentPath(currentPath, item.url)}
                                render={
                                    <Link
                                        href={item.url}
                                        className="flex w-full items-center gap-2"
                                    />
                                }
                            >
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
