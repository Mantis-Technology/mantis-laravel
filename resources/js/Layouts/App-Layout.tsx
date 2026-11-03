import type { PropsWithChildren } from 'react';
import { AppSidebar } from '@/Components/App-Sidebar';
import { FlashToasts } from '@/Components/Flash-Toasts';
import {
    SidebarInset,
    SidebarProvider,
    SidebarTrigger,
} from '@/Components/Ui/Sidebar';
import { Toaster } from '@/Components/Ui/Sonner';

export default function AppLayout({ children }: PropsWithChildren) {
    return (
        <SidebarProvider>
            <AppSidebar />

            <Toaster richColors position="top-right" />

            <FlashToasts />

            <SidebarInset>
                <header className="flex h-16 shrink-0 items-center gap-2 border-b px-4">
                    <SidebarTrigger />
                </header>

                <main className="flex flex-1 flex-col gap-4 p-4">
                    {children}
                </main>
            </SidebarInset>
        </SidebarProvider>
    );
}
