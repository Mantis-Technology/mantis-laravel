import { FormEventHandler } from 'react'
import { useForm } from '@inertiajs/react'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

type PasswordForm = {
    current_password: string
    password: string
    password_confirmation: string
}

export default function UpdatePassword() {
    const {
        data,
        setData,
        put,
        processing,
        recentlySuccessful,
        errors,
        reset,
    } = useForm<PasswordForm>({
        current_password: '',
        password: '',
        password_confirmation: '',
    })

    const submit: FormEventHandler = (event) => {
        event.preventDefault()

        put('/user/password', {
            preserveScroll: true,
            onSuccess: () => {
                reset(
                    'current_password',
                    'password',
                    'password_confirmation',
                )
            },
        })
    }

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="space-y-2">
                <Label htmlFor="current_password">
                    Current password
                </Label>

                <Input
                    id="current_password"
                    type="password"
                    autoComplete="current-password"
                    value={data.current_password}
                    onChange={(event) =>
                        setData('current_password', event.target.value)
                    }
                    disabled={processing}
                />

                {errors.current_password && (
                    <p className="text-sm text-destructive">
                        {errors.current_password}
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
                    Confirm new password
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

            {recentlySuccessful && (
                <p className="text-sm font-medium text-green-600">
                    Password updated successfully.
                </p>
            )}

            <div className="flex justify-end">
                <Button
                    type="submit"
                    disabled={processing}
                >
                    {processing ? 'Updating...' : 'Update password'}
                </Button>
            </div>
        </form>
    )
}