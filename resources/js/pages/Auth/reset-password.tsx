import { FormEventHandler } from 'react'
import { Head, useForm } from '@inertiajs/react'

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

type ResetPasswordForm = {
    token: string
    email: string
    password: string
    password_confirmation: string
}

type ResetPasswordProps = {
    token: string
    email: string
}

export default function ResetPassword({
    token,
    email,
}: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } =
        useForm<ResetPasswordForm>({
            token,
            email,
            password: '',
            password_confirmation: '',
        })

    const submit: FormEventHandler = (event) => {
        event.preventDefault()

        post('/reset-password', {
            onFinish: () =>
                reset('password', 'password_confirmation'),
        })
    }

    return (
        <>
            <Head title="Reset password" />

            <div className="flex min-h-svh items-center justify-center bg-muted/40 p-6">
                <Card className="w-full max-w-md">
                    <CardHeader>
                        <CardTitle>Reset your password</CardTitle>

                        <CardDescription>
                            Enter your new password below.
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <input
                                type="hidden"
                                name="token"
                                value={data.token}
                            />

                            <div className="space-y-2">
                                <Label htmlFor="email">
                                    Email
                                </Label>

                                <Input
                                    id="email"
                                    type="email"
                                    autoComplete="email"
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

                            <div className="space-y-2">
                                <Label htmlFor="password">
                                    New password
                                </Label>

                                <Input
                                    id="password"
                                    type="password"
                                    autoComplete="new-password"
                                    autoFocus
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

                            <div className="space-y-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>

                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    autoComplete="new-password"
                                    value={data.password_confirmation}
                                    onChange={(event) =>
                                        setData(
                                            'password_confirmation',
                                            event.target.value,
                                        )
                                    }
                                    disabled={processing}
                                />

                                {errors.password_confirmation && (
                                    <p className="text-sm text-destructive">
                                        {errors.password_confirmation}
                                    </p>
                                )}
                            </div>

                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing
                                    ? 'Resetting password...'
                                    : 'Reset password'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    )
}