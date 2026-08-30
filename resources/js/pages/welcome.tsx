import { Head, Link, router, usePage } from '@inertiajs/react'
import { LayoutDashboard, LogIn, LogOut } from 'lucide-react'

import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'

type User = {
    id: number
    name: string
    email: string
}

type PageProps = {
    auth: {
        user: User | null
    }
}

export default function Welcome() {
    const { auth } = usePage<PageProps>().props

    const logout = () => {
        router.post('/logout')
    }

    const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

    return (
        <>
            <Head title="Welcome" />

            <div className="min-h-svh bg-background">
                <header className="border-b">
                    <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                        <Link
                            href="/"
                            className="font-semibold tracking-tight"
                        >
                            {appName}
                        </Link>

                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <>
                                    <Button
                                        variant="ghost"
                                        render={
                                            <Link href="/dashboard">
                                                <LayoutDashboard />
                                                Dashboard
                                            </Link>
                                        }
                                    />

                                    <Button
                                        variant="outline"
                                        onClick={logout}
                                    >
                                        <LogOut />
                                        Log out
                                    </Button>
                                </>
                            ) : (
                                <>
                                    <Button
                                        variant="ghost"
                                        render={
                                            <Link href="/login">
                                                <LogIn />
                                                Login
                                            </Link>
                                        }
                                    />
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex min-h-[calc(100svh-4rem)] items-center justify-center px-6 py-12">
                    <div className="w-full max-w-3xl">
                        <Card>
                            <CardHeader className="text-center">
                                <CardTitle className="text-4xl tracking-tight">
                                    {auth.user
                                        ? `Welcome back, ${auth.user.name}`
                                        : 'Welcome'}
                                </CardTitle>

                                <CardDescription className="text-base">
                                    {auth.user
                                        ? 'You are signed in and ready to continue.'
                                        : 'Sign in to your account to get started.'}
                                </CardDescription>
                            </CardHeader>

                            <CardContent className="flex justify-center gap-3">
                                {auth.user ? (
                                    <>
                                        <Button
                                            size="lg"
                                            render={
                                                <Link href="/dashboard">
                                                    <LayoutDashboard />
                                                    Open dashboard
                                                </Link>
                                            }
                                        />

                                        <Button
                                            size="lg"
                                            variant="outline"
                                            render={
                                                <Link href="/profile">
                                                    Profile
                                                </Link>
                                            }
                                        />
                                    </>
                                ) : (
                                    <>
                                        <Button
                                            size="lg"
                                            render={
                                                <Link href="/login">
                                                    <LogIn />
                                                    Sign in
                                                </Link>
                                            }
                                        />
                                    </>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </main>
            </div>
        </>
    )
}