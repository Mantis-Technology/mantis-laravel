import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType, ReactNode } from 'react';
import { createRoot } from 'react-dom/client';

import AppLayout from '@/Layouts/app-layout';

type PageComponent = ComponentType<any> & {
    layout?: (page: ReactNode) => ReactNode;
};

type PageModule = {
    default: PageComponent;
};

const pages = import.meta.glob<PageModule>('./Pages/**/*.tsx');

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    resolve: async (name) => {
        const path = `./Pages/${name}.tsx`;
        const resolver = pages[path];

        if (!resolver) {
            throw new Error(
                `Inertia page not found: ${name}\n` +
                    `Expected: ${path}\n` +
                    `Available:\n${Object.keys(pages).join('\n')}`,
            );
        }

        const module = await resolver();
        const Page = module.default;

        if (
            name !== 'welcome' &&
            name !== 'access' &&
            name !== 'company-portal' &&
            name !== 'tenant-register' &&
            name !== 'tenant-register-success' &&
            !name.startsWith('Auth/')
        ) {
            Page.layout ??= (page: ReactNode) => <AppLayout>{page}</AppLayout>;
        }

        return Page;
    },

    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
