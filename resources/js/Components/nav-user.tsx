import {
  Avatar,
  AvatarFallback,
  AvatarImage,
} from '@/components/ui/avatar'

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from '@/components/ui/sidebar'

import { Link, router, usePage } from '@inertiajs/react'

import {
  BellIcon,
  CreditCardIcon,
  EllipsisVerticalIcon,
  LogOutIcon,
  UserIcon,
} from 'lucide-react'

export function NavUser() {
  const { isMobile } = useSidebar()
  const { auth } = usePage().props

  if (!auth.user) {
    return null
  }

  const user = auth.user

  const initials = user.name
    .split(' ')
    .filter(Boolean)
    .map((name) => name[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()

  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <DropdownMenu>
          <DropdownMenuTrigger
            render={
              <SidebarMenuButton
                size="lg"
                className="aria-expanded:bg-muted"
              />
            }
          >
            <Avatar className="size-8 rounded-lg grayscale">
              {user.avatar && (
                <AvatarImage
                  src={user.avatar}
                  alt={user.name}
                />
              )}

              <AvatarFallback className="rounded-lg">
                {initials}
              </AvatarFallback>
            </Avatar>

            <div className="grid flex-1 text-left text-sm leading-tight">
              <span className="truncate font-medium">
                {user.name}
              </span>

              <span className="truncate text-xs text-foreground/70">
                {user.email}
              </span>
            </div>

            <EllipsisVerticalIcon className="ml-auto size-4" />
          </DropdownMenuTrigger>

          <DropdownMenuContent
            className="min-w-56"
            side={isMobile ? 'bottom' : 'right'}
            align="end"
            sideOffset={4}
          >
            <DropdownMenuGroup>
              <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                  <Avatar className="size-8">
                    {user.avatar && (
                      <AvatarImage
                        src={user.avatar}
                        alt={user.name}
                      />
                    )}

                    <AvatarFallback className="rounded-lg">
                      {initials}
                    </AvatarFallback>
                  </Avatar>

                  <div className="grid flex-1 text-left text-sm leading-tight">
                    <span className="truncate font-medium">
                      {user.name}
                    </span>

                    <span className="truncate text-xs text-muted-foreground">
                      {user.email}
                    </span>
                  </div>
                </div>
              </DropdownMenuLabel>

              <DropdownMenuItem
                render={
                  <Link href="/profile">
                    <UserIcon />
                    Profile
                  </Link>
                }
              />

            </DropdownMenuGroup>

            <DropdownMenuSeparator />

            <DropdownMenuItem
              onClick={() => router.post('/logout')}
            >
              <LogOutIcon />
              Log out
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarMenuItem>
    </SidebarMenu>
  )
}