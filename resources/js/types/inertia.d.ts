import '@inertiajs/core'

import type { User } from './auth'

declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: {
            auth: {
                user: User | null
            }
        }
    }
}

export {}