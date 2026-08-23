import { createInertiaApp } from '@inertiajs/react'
import createServer from '@inertiajs/react/server'

const pages = import.meta.glob('./Pages/**/*.tsx')

createServer(
    async (page) =>
        createInertiaApp({
            page,
            render: (element) => element,
            resolve: async (name) => {
                const path = `./Pages/${name}.tsx`
                const resolver = pages[path]

                if (!resolver) {
                    throw new Error(
                        `Inertia page not found: ${name}\nExpected: ${path}\nAvailable:\n${Object.keys(pages).join('\n')}`,
                    )
                }

                return resolver()
            },
            setup({ App, props }) {
                return <App {...props} />
            },
        }),
)