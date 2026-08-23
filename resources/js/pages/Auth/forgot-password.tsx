import { FormEventHandler } from 'react'
import { Head, Link, useForm, usePage } from '@inertiajs/react'

import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

type ForgotPasswordForm = {
    email: string
}

type PageProps = {
    status?: string
}

export default function ForgotPassword() {
    const { status } = usePage<PageProps>().props

    const { data, setData, post, processing, errors } =
        useForm<ForgotPasswordForm>({
            email: '',
        })

    const submit: FormEventHandler = (event) => {
        event.preventDefault()

        post('/forgot-password')
    }

    return (
        <>
            <Head title="Forgot password" />

            <div className="flex min-h-svh items-center justify-center bg-muted/40 p-6">
                <Card className="w-full max-w-md">
                    <CardHeader>
                        <CardTitle>Forgot your password?</CardTitle>

                        <CardDescription>
                            Enter your email and we'll send you a password
                            reset link.
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
                                    disabled={processing}
                                />

                                {errors.email && (
                                    <p className="text-sm text-destructive">
                                        {errors.email}
                                    </p>
                                )}
                            </div>

                            {status && (
                                <p className="text-sm font-medium text-green-600">
                                    {status}
                                </p>
                            )}

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing
                                    ? 'Sending link...'
                                    : 'Email password reset link'}
                            </Button>

                            <div className="text-center text-sm">
                                <Link
                                    href="/login"
                                    className="font-medium underline underline-offset-4"
                                >
                                    Back to login
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    )
}