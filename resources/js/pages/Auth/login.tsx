import { FormEventHandler } from 'react'
import { Head, Link, useForm } from '@inertiajs/react'

import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

type LoginForm = {
    email: string
    password: string
    remember: boolean
}

export default function Login() {
    const { data, setData, post, processing, errors, reset } =
        useForm<LoginForm>({
            email: '',
            password: '',
            remember: false,
        })

    const submit: FormEventHandler = (event) => {
        event.preventDefault()

        post('/login', {
            onFinish: () => reset('password'),
        })
    }

    return (
        <>
            <Head title="Log in" />

            <div className="flex min-h-svh items-center justify-center bg-muted/40 p-6">
                <Card className="w-full max-w-md">
                    <CardHeader className="space-y-1">
                        <CardTitle className="text-2xl">
                            Welcome back
                        </CardTitle>

                        <CardDescription>
                            Enter your email and password to access your
                            account.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="email">
                                    Email
                                </Label>

                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="email"
                                    autoFocus
                                    value={data.email}
                                    onChange={(event) =>
                                        setData('email', event.target.value)
                                    }
                                    placeholder="you@example.com"
                                    disabled={processing}
                                />

                                {errors.email && (
                                    <p className="text-sm text-destructive">
                                        {errors.email}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="password">
                                        Password
                                    </Label>

                                    <Link
                                        href="/forgot-password"
                                        className="text-sm font-medium underline-offset-4 hover:underline"
                                    >
                                        Forgot password?
                                    </Link>
                                </div>

                                <Input
                                    id="password"
                                    type="password"
                                    autoComplete="current-password"
                                    value={data.password}
                                    onChange={(event) =>
                                        setData('password', event.target.value)
                                    }
                                    disabled={processing}
                                />

                                {errors.password && (
                                    <p className="text-sm text-destructive">
                                        {errors.password}
                                    </p>
                                )}
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="remember"
                                    checked={data.remember}
                                    onCheckedChange={(checked) =>
                                        setData('remember', checked === true)
                                    }
                                    disabled={processing}
                                />

                                <Label
                                    htmlFor="remember"
                                    className="font-normal"
                                >
                                    Remember me
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing ? 'Signing in...' : 'Sign in'}
                            </Button>

                            <div className="text-center text-sm text-muted-foreground">
                                Don&apos;t have an account?{' '}
                                <Link
                                    href="/register"
                                    className="font-medium text-foreground underline underline-offset-4"
                                >
                                    Create one
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    )
}