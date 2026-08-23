import '../css/app.css'

import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

const pages = import.meta.glob('./Pages/**/*.tsx')

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    resolve: async (name) => {
        const path = `./Pages/${name}.tsx`
        const page = pages[path]

        if (!page) {
            throw new Error(
                `Inertia page not found: ${name}\nExpected: ${path}\nAvailable:\n${Object.keys(pages).join('\n')}`,
            )
        }

        return page()
    },

    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />)
    },
})