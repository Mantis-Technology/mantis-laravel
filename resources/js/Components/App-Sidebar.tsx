import { Link, usePage } from '@inertiajs/react';
import {
    AlertTriangleIcon,
    BoxesIcon,
    CalendarCheckIcon,
    CalendarClockIcon,
    ChevronRight,
    ClipboardListIcon,
    CommandIcon,
    FolderIcon,
    GaugeIcon,
    LayoutDashboardIcon,
    LayoutTemplateIcon,
    ListIcon,
    MapPinIcon,
    UsersIcon,
    ShieldIcon,
    KeyRoundIcon,
} from 'lucide-react';
import * as React from 'react';

import { NavMain } from '@/Components/Nav-Main';
import { NavUser } from '@/Components/Nav-User';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/Components/Ui/Collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/Components/Ui/Sidebar';
import { isTenantAdmin } from '@/Lib/Access';
import type { User } from '@/Types/Auth';

type Tenant = {
    id: string;
    name: string;
    logo?: string | null;
    description?: string | null;
};

type PageProps = {
    tenant: Tenant | null;
    auth: {
        user: User | null;
    };
};

const data = {
    navMain: [
        {
            title: 'Dashboard',
            url: '/dashboard',
            icon: LayoutDashboardIcon,
        },
        {
            title: 'Casos de mantenimiento',
            url: '/tickets',
            icon: ClipboardListIcon,
        },
        {
            title: 'Fichas de activos',
            url: '/asset-cards',
            icon: BoxesIcon,
        },
        {
            title: 'Reportar incidencia',
            url: '/tickets/create',
            icon: AlertTriangleIcon,
        },
    ],

    preventive: [
        {
            title: 'Planes preventivos',
            url: '/preventive-plans',
            icon: CalendarClockIcon,
        },
        {
            title: 'Ejecuciones preventivas',
            url: '/preventive-executions',
            icon: CalendarCheckIcon,
        },
    ],

    parameterization: [
        {
            title: 'Categorías de Mantenimiento',
            url: '/parameterization/maintenance-categories',
            icon: ListIcon,
        },
        {
            title: 'Sedes y ubicaciones',
            url: '/parameterization/locations',
            icon: MapPinIcon,
        },
        {
            title: 'Niveles de servicio',
            url: '/parameterization/service-levels',
            icon: GaugeIcon,
        },
        {
            title: 'Plantillas de activos',
            url: '/asset-card-templates',
            icon: LayoutTemplateIcon,
        },
    ],

    administration: [
        {
            title: 'Usuarios',
            url: '/users',
            icon: UsersIcon,
        },
        {
            title: 'Roles',
            url: '/roles',
            icon: ShieldIcon,
        },
        {
            title: 'Permisos',
            url: '/permissions',
            icon: KeyRoundIcon,
        },
    ],
};

export function AppSidebar(props: React.ComponentProps<typeof Sidebar>) {
    const { tenant, auth } = usePage<PageProps>().props;
    const showAdministration = isTenantAdmin(auth.user);

    return (
        <Sidebar collapsible="offcanvas" {...props}>
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            className="h-auto w-full flex-col items-center justify-center gap-2 p-4!"
                            render={<Link href="/" />}
                        >
                            {tenant?.logo ? (
                                <img
                                    src="/tenant/logo"
                                    alt={tenant.name}
                                    className="aspect-video w-full max-w-40 object-contain"
                                />
                            ) : (
                                <CommandIcon className="size-10!" />
                            )}

                            <span className="w-full truncate text-center text-base font-semibold">
                                {tenant?.name ?? 'Tenant'}
                            </span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={data.navMain} />

                <SidebarGroup>
                    <SidebarMenu>
                        <Collapsible defaultOpen className="group/collapsible">
                            <SidebarMenuItem>
                                <CollapsibleTrigger
                                    render={
                                        <SidebarMenuButton tooltip="Mantenimiento preventivo" />
                                    }
                                >
                                    <CalendarClockIcon className="size-4" />

                                    <span>Mantenimiento preventivo</span>

                                    <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                </CollapsibleTrigger>

                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {data.preventive.map((item) => (
                                            <SidebarMenuSubItem
                                                key={item.title}
                                            >
                                                <SidebarMenuSubButton
                                                    render={
                                                        <Link href={item.url} />
                                                    }
                                                >
                                                    {item.icon && <item.icon />}
                                                    <span>{item.title}</span>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        ))}
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    </SidebarMenu>
                </SidebarGroup>

                <SidebarGroup>
                    <SidebarMenu>
                        <Collapsible defaultOpen className="group/collapsible">
                            <SidebarMenuItem>
                                <CollapsibleTrigger
                                    render={
                                        <SidebarMenuButton tooltip="Parametrización" />
                                    }
                                >
                                    <FolderIcon className="size-4" />

                                    <span>Parametrización</span>

                                    <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                </CollapsibleTrigger>

                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {data.parameterization.map((item) => (
                                            <SidebarMenuSubItem
                                                key={item.title}
                                            >
                                                <SidebarMenuSubButton
                                                    render={
                                                        <Link href={item.url} />
                                                    }
                                                >
                                                    {item.icon && <item.icon />}
                                                    <span>{item.title}</span>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        ))}
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    </SidebarMenu>
                </SidebarGroup>

                {showAdministration && (
                    <SidebarGroup>
                        <SidebarMenu>
                            <Collapsible
                                defaultOpen
                                className="group/collapsible"
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger
                                        render={
                                            <SidebarMenuButton tooltip="Administración" />
                                        }
                                    >
                                        <ShieldIcon className="size-4" />

                                        <span>Administración</span>

                                        <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                    </CollapsibleTrigger>

                                    <CollapsibleContent>
                                        <SidebarMenuSub>
                                            {data.administration.map((item) => (
                                                <SidebarMenuSubItem
                                                    key={item.title}
                                                >
                                                    <SidebarMenuSubButton
                                                        render={
                                                            <Link
                                                                href={item.url}
                                                            />
                                                        }
                                                    >
                                                        {item.icon && (
                                                            <item.icon />
                                                        )}

                                                        <span>
                                                            {item.title}
                                                        </span>
                                                    </SidebarMenuSubButton>
                                                </SidebarMenuSubItem>
                                            ))}
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>
                        </SidebarMenu>
                    </SidebarGroup>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
