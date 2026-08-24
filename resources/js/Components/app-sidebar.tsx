import * as React from 'react'

import { NavMain } from '@/components/nav-main'
import { NavUser } from '@/components/nav-user'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar'

import { usePage } from '@inertiajs/react'

import {
  ChartBarIcon,
  FolderIcon,
  LayoutDashboardIcon,
  ListIcon,
  UsersIcon,
  CommandIcon,
} from 'lucide-react'

type Tenant = {
  id: string
  name: string
  logo?: string | null
  description?: string | null
}

type PageProps = {
  tenant: Tenant | null
}

const data = {
  navMain: [
    {
      title: 'Dashboard',
      url: '/dashboard',
      icon: <LayoutDashboardIcon />,
    },
    {
      title: 'Lifecycle',
      url: '#',
      icon: <ListIcon />,
    },
    {
      title: 'Analytics',
      url: '#',
      icon: <ChartBarIcon />,
    },
    {
      title: 'Projects',
      url: '#',
      icon: <FolderIcon />,
    },
    {
      title: 'Team',
      url: '#',
      icon: <UsersIcon />,
    },
  ],
}

export function AppSidebar(
  props: React.ComponentProps<typeof Sidebar>,
) {
  const { tenant } = usePage<PageProps>().props

  return (
    <Sidebar collapsible="offcanvas" {...props}>
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton
              className="h-auto w-full flex-col items-center justify-center gap-2 p-4!"
              render={<a href="/" />}
            >
              {tenant?.logo ? (
                <img
                  src="/tenant/logo"
                  alt={tenant.name}
                  className="w-full object-contain"
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
      </SidebarContent>

      <SidebarFooter>
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  )
}