import { Head, Link, usePage } from '@inertiajs/react';

import { Card, CardContent, CardHeader, CardTitle } from '@/Components/Ui/Card';
import { Separator } from '@/Components/Ui/Separator';

import UpdatePassword from './Partials/Update-Password';
import UpdateProfileInformation from './Partials/Update-Profile-Information';

type User = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
};

type PageProps = {
    auth: {
        user: User;
    };
};

export default function Edit() {
    const { auth } = usePage<PageProps>().props;

    return (
        <>
            <Head title="Profile" />

            <div className="mx-auto flex w-full flex-col gap-8 p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Profile
                    </h1>

                    <p className="text-muted-foreground">
                        Manage your profile and account settings.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Profile information</CardTitle>
                    </CardHeader>

                    <CardContent>
                        <UpdateProfileInformation user={auth.user} />
                    </CardContent>
                </Card>

                <Separator />

                <Card>
                    <CardHeader>
                        <CardTitle>Update password</CardTitle>
                    </CardHeader>

                    <CardContent>
                        <UpdatePassword />
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end">
                    <Link
                        href="/dashboard"
                        className="text-sm font-medium underline underline-offset-4"
                    >
                        Back to dashboard
                    </Link>
                </div>
            </div>
        </>
    );
}
