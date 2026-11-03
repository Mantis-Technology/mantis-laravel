import type { Auth } from './Auth'

declare module '@inertiajs/core' {
    interface PageProps {
        auth: Auth
    }
}