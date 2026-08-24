import { Head } from '@inertiajs/react'

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Dashboard
                </h1>
            </div>
        </>
    )
}