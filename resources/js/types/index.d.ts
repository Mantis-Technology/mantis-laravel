import type { Auth } from './auth'

declare module '@inertiajs/core' {
    interface PageProps {
        auth: Auth
    }
}