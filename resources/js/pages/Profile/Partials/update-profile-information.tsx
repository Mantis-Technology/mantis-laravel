import { FormEventHandler } from 'react'
import { useForm } from '@inertiajs/react'

import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

type User = {
    id: number
    name: string
    email: string
    email_verified_at: string | null
}

type Props = {
    user: User
}

type ProfileForm = {
    name: string
    email: string
}

export default function UpdateProfileInformation({ user }: Props) {
    const {
        data,
        setData,
        put,
        processing,
        recentlySuccessful,
        errors,
    } = useForm<ProfileForm>({
        name: user.name,
        email: user.email,
    })

    const submit: FormEventHandler = (event) => {
        event.preventDefault()

        put('/user/profile-information', {
            preserveScroll: true,
        })
    }

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="space-y-2">
                <Label htmlFor="name">
                    Name
                </Label>

                <Input
                    id="name"
                    type="text"
                    autoComplete="name"
                    value={data.name}
                    onChange={(event) =>
                        setData('name', event.target.value)
                    }
                    disabled={processing}
                />

                {errors.name && (
                    <p className="text-sm text-destructive">
                        {errors.name}
                    </p>
                )}
            </div>

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

            {user.email_verified_at === null && (
                <div className="rounded-lg border border-yellow-500/20 bg-yellow-500/10 p-4">
                    <p className="text-sm text-yellow-700 dark:text-yellow-400">
                        Your email address is not verified.
                    </p>
                </div>
            )}

            {recentlySuccessful && (
                <p className="text-sm font-medium text-green-600">
                    Profile updated successfully.
                </p>
            )}

            <div className="flex justify-end">
                <Button
                    type="submit"
                    disabled={processing}
                >
                    {processing ? 'Saving...' : 'Save changes'}
                </Button>
            </div>
        </form>
    )
}