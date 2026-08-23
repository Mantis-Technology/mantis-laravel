import { PropsWithChildren } from 'react'

import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from '@/components/ui/sidebar'

import { AppSidebar } from '@/components/AppSidebar'

export default function AppLayout({ children }: PropsWithChildren) {
    return (
        <SidebarProvider>
            <AppSidebar />

            <SidebarInset>
                <header className="flex h-16 shrink-0 items-center gap-2 border-b px-4">
                    <SidebarTrigger />
                </header>

                <main className="flex flex-1 flex-col gap-4 p-4">
                    {children}
                </main>
            </SidebarInset>
        </SidebarProvider>
    )
}