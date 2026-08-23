import { FormEventHandler } from 'react'
import { Head, useForm, usePage } from '@inertiajs/react'

import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'

type PageProps = {
    auth: {
        user: {
            name: string
            email: string
        } | null
    }
    status?: string
}

export default function VerifyEmail() {
    const { auth, status } = usePage<PageProps>().props

    const { post, processing } = useForm({})

    const submit: FormEventHandler = (event) => {
        event.preventDefault()

        post('/email/verification-notification')
    }

    return (
        <>
            <Head title="Verify email" />

            <div className="flex min-h-svh items-center justify-center bg-muted/40 p-6">
                <Card className="w-full max-w-md">
                    <CardHeader>
                        <CardTitle>Verify your email</CardTitle>

                        <CardDescription>
                            We sent a verification link to{' '}
                            <span className="font-medium text-foreground">
                                {auth.user?.email}
                            </span>
                            .
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="space-y-6">
                        <p className="text-sm text-muted-foreground">
                            Please check your inbox and click the verification
                            link to activate your account.
                        </p>

                        {status === 'verification-link-sent' && (
                            <p className="text-sm font-medium text-green-600">
                                A new verification link has been sent to your
                                email.
                            </p>
                        )}

                        <form onSubmit={submit}>
                            <Button
                                type="submit"
                                className="w-full"
                                disabled={processing}
                            >
                                {processing
                                    ? 'Sending...'
                                    : 'Resend verification email'}
                            </Button>
                        </form>

                        <form
                            method="POST"
                            action="/logout"
                            className="text-center"
                        >
                            <button
                                type="submit"
                                className="text-sm font-medium underline underline-offset-4"
                            >
                                Log out
                            </button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    )
}