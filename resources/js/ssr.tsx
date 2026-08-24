import { createInertiaApp } from '@inertiajs/react'
import createServer from '@inertiajs/react/server'
import { renderToString } from 'react-dom/server'
import type { ComponentType, ReactNode } from 'react'

import AppLayout from '@/Layouts/app-layout'

type PageComponent = ComponentType<any> & {
    layout?: (page: ReactNode) => ReactNode
}

type PageModule = {
    default: PageComponent
}

const pages = import.meta.glob<PageModule>('./Pages/**/*.tsx')

createServer(
    async (page) =>
        createInertiaApp({
            page,

            render: (element) => renderToString(element),

            resolve: async (name) => {
                const path = `./Pages/${name}.tsx`
                const resolver = pages[path]

                if (!resolver) {
                    throw new Error(
                        `Inertia page not found: ${name}\n` +
                        `Expected: ${path}\n` +
                        `Available:\n${Object.keys(pages).join('\n')}`,
                    )
                }

                const module = await resolver()
                const Page = module.default

                if (
                    name !== 'welcome' &&
                    !name.startsWith('Auth/')
                ) {
                    Page.layout ??= (page: ReactNode) => (
                        <AppLayout>{page}</AppLayout>
                    )
                }

                return Page
            },

            setup({ App, props }) {
                return <App {...props} />
            },
        }),
)