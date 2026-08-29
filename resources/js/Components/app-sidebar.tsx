import * as React from 'react'

import { NavMain } from '@/components/nav-main'
import { NavUser } from '@/components/nav-user'
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
} from '@/components/ui/sidebar'

import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'

import { usePage } from '@inertiajs/react'

import {
  FolderIcon,
  LayoutDashboardIcon,
  ListIcon,
  ChevronRight,
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
      icon: LayoutDashboardIcon,
    },
  ],

  parameterization: [
    {
      title: 'Categorías de Mantenimiento',
      url: '/parameterization/maintenance-categories',
      icon: ListIcon,
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

        <SidebarGroup>
          <SidebarMenu>
            <Collapsible
              defaultOpen
              className="group/collapsible"
            >
              <SidebarMenuItem>
                <CollapsibleTrigger
                  render={
                    <SidebarMenuButton
                      tooltip="Parametrización"
                    />
                  }
                >
                  <FolderIcon className="size-4" />

                  <span>Parametrización</span>

                  <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                </CollapsibleTrigger>

                <CollapsibleContent>
                  <SidebarMenuSub>
                    {data.parameterization.map((item) => (
                      <SidebarMenuSubItem key={item.title}>
                        <SidebarMenuSubButton
                          render={<a href={item.url} />}
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
      </SidebarContent>

      <SidebarFooter>
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  )
}