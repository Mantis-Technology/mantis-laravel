import '@inertiajs/core'

import type { User } from './auth'

declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: {
            auth: {
                user: User | null
            }
            
            tenant: Tenant | null

            maintenance_categories: MaintenanceCategory[];
        }
    }
}

export {}