import { Head, Link } from '@inertiajs/react';
import { gsap } from 'gsap';
import { Building2, QrCode } from 'lucide-react';
import { useEffect, useRef } from 'react';

type Portal = {
    code: string;
    title: string;
    description: string;
    scope: string;
    action: string;
    href: string;
    external?: boolean;
    accent: 'signal' | 'warn';
    isPrimary: boolean;
};

const PORTALS: Portal[] = [
    {
        code: 'PTL-01',
        title: 'Panel de Administración',
        description:
            'Acceso exclusivo para administradores del sistema. Gestión de infraestructura, monitoreo global de activos y configuración de tenants.',
        scope: 'System Admins Only',
        action: 'Entrar al panel',
        href: '/admin',
        external: true,
        accent: 'signal',
        isPrimary: true,
    },
    {
        code: 'PTL-02',
        title: 'Portal de Empresas',
        description:
            'Acceso para organizaciones registradas. Visualización de flotas, control de mantenimiento preventivo y analíticas de rendimiento por unidad.',
        scope: 'Registered Tenants',
        action: 'Entrar al portal',
        href: '/portal',
        accent: 'warn',
        isPrimary: false,
    },
];

export default function Access({ adminUrl }: { adminUrl?: string }) {
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const root = rootRef.current;

        if (!root) {
            return;
        }

        const ctx = gsap.context(() => {
            const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

            tl.fromTo(
                '.access-eyebrow',
                { opacity: 0, y: 12 },
                { opacity: 1, y: 0, duration: 0.5 },
            )
                .fromTo(
                    '.access-title',
                    { opacity: 0, y: 20 },
                    { opacity: 1, y: 0, duration: 0.6 },
                    '-=0.25',
                )
                .fromTo(
                    '.access-sub',
                    { opacity: 0, y: 12 },
                    { opacity: 1, y: 0, duration: 0.5 },
                    '-=0.35',
                )
                .fromTo(
                    '.portal-card',
                    { opacity: 0, y: 28 },
                    {
                        opacity: 1,
                        y: 0,
                        duration: 0.6,
                        stagger: 0.12,
                    },
                    '-=0.2',
                );
        }, root);

        return () => ctx.revert();
    }, []);

    return (
        <div
            ref={rootRef}
            className="relative isolate flex min-h-svh flex-col bg-background text-foreground"
        >
            <Head title="Selección de Acceso" />

            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(55%_60%_at_90%_0%,rgba(217,138,108,0.22),transparent_70%),radial-gradient(45%_55%_at_0%_100%,rgba(47,122,130,0.16),transparent_70%)]"
            />

            <div className="h-1.5 w-full bg-linear-to-r from-[#343536] via-[#B85C43] to-[#2F7A82]" />

            <div className="sticky top-0 z-50 w-full border-b border-white/10 bg-linear-to-r from-[#2c2d2e] to-[#3a3b3c] shadow-md">
                <nav className="mx-auto flex w-full max-w-[1440px] items-center justify-between px-6 py-4">
                <Link href="/" className="flex items-center gap-4">
                    <img
                        alt="MANTIS Industrial Logo"
                        className="h-9 w-auto object-contain md:h-11"
                        src="/logo.png"
                    />
                </Link>

                <span className="hidden font-mono text-[12px] tracking-[0.05em] text-[#C9CBCC] uppercase md:inline">
                    Access_OPS / Portal Select
                </span>
            </nav>
            </div>

            <main className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col items-center justify-center px-6 py-20">
                <div className="mb-12 max-w-2xl text-center">
                    <p className="access-eyebrow mb-5 font-mono text-[14px] tracking-[0.05em] text-accent-foreground dark:text-primary uppercase">
                        Seleccione su destino
                    </p>

                    <h1 className="access-title text-[48px] leading-[56px] font-extrabold tracking-[-0.02em] text-foreground uppercase">
                        Portal de acceso
                    </h1>

                    <p className="access-sub mx-auto mt-5 max-w-xl text-[18px] leading-[28px] text-muted-foreground">
                        Elija su destino para continuar con la autenticación en
                        el ecosistema industrial MANTIS.
                    </p>
                </div>

                <div className="grid w-full max-w-5xl grid-cols-1 gap-4 md:grid-cols-2">
                    {PORTALS.map((portal) => {
                        const href = portal.external
                            ? (adminUrl ?? portal.href)
                            : portal.href;

                        const dark = portal.isPrimary;
                        const soft = dark
                            ? 'text-[#C9CBCC]'
                            : 'text-muted-foreground';

                        const content = (
                            <>
                                <div
                                    className={`relative z-10 flex size-11 items-center justify-center rounded-xl ${
                                        dark
                                            ? 'bg-[#D98A6C]/20 text-[#D98A6C]'
                                            : 'bg-teal/10 text-teal'
                                    }`}
                                >
                                    {dark ? (
                                        <Building2 className="size-5" />
                                    ) : (
                                        <QrCode className="size-5" />
                                    )}
                                </div>

                                <h2 className="relative z-10 mt-5 text-[24px] leading-[32px] font-semibold">
                                    {portal.title}
                                </h2>

                                <p
                                    className={`relative z-10 mt-2 mb-6 text-[16px] leading-[24px] ${soft}`}
                                >
                                    {portal.description}
                                </p>

                                <div className="relative z-10 mt-auto flex items-center justify-between gap-3">
                                    <span
                                        className={`font-mono text-[12px] tracking-[0.05em] uppercase ${soft}`}
                                    >
                                        {portal.scope}
                                    </span>

                                    <span
                                        className={`inline-flex items-center gap-2 rounded-lg px-4 py-2 font-mono text-[13px] tracking-[0.05em] uppercase shadow-sm transition-all group-hover:shadow-md ${
                                            dark
                                                ? 'bg-[#D98A6C] text-[#2b2c2d]'
                                                : 'bg-teal text-white'
                                        }`}
                                    >
                                        {portal.action}
                                        <span className="transition-transform duration-300 group-hover:translate-x-0.5">
                                            →
                                        </span>
                                    </span>
                                </div>
                            </>
                        );

                        const cardClasses = `portal-card group relative flex flex-col overflow-hidden rounded-2xl p-8 transition-all duration-300 hover:-translate-y-1 sm:p-10 ${
                            dark
                                ? 'bg-linear-to-br from-[#3a3b3c] via-[#2b2c2d] to-[#3d2f2a] text-[#F0E8DF] shadow-xl shadow-[#343536]/30 before:pointer-events-none before:absolute before:-top-16 before:-right-16 before:size-56 before:rounded-full before:bg-[radial-gradient(circle,rgba(217,138,108,0.4),transparent_70%)]'
                                : 'border border-t-4 border-border border-t-teal bg-card text-card-foreground shadow-md hover:shadow-xl'
                        }`;

                        return portal.external ? (
                            <a
                                key={portal.code}
                                href={href}
                                className={cardClasses}
                            >
                                {content}
                            </a>
                        ) : (
                            <Link
                                key={portal.code}
                                href={href}
                                className={cardClasses}
                            >
                                {content}
                            </Link>
                        );
                    })}
                </div>

                <div className="mt-12 flex items-center gap-4">
                    <div className="h-px flex-1 bg-border" />
                    <p className="font-mono text-[12px] tracking-[0.05em] text-muted-foreground uppercase">
                        ¿Aún no es cliente?
                    </p>
                    <div className="h-px flex-1 bg-border" />
                </div>

                <Link
                    href="/register/company"
                    className="mt-6 inline-flex items-center gap-2 rounded-lg border border-primary px-6 py-3 font-mono text-[14px] tracking-[0.05em] text-accent-foreground dark:text-primary uppercase transition-colors hover:bg-primary hover:text-primary-foreground"
                >
                    Registrar mi empresa
                    <span aria-hidden="true">→</span>
                </Link>
            </main>

            <footer className="border-t border-border bg-muted">
                <div className="mx-auto flex w-full max-w-[1440px] flex-col items-center justify-between gap-6 px-6 py-8 md:flex-row">
                    <div className="font-mono text-[14px] font-bold text-foreground">
                        MANTIS
                    </div>

                    <div className="flex flex-wrap justify-center gap-6">
                        <a
                            href="#"
                            className="font-mono text-[12px] text-muted-foreground underline transition-opacity duration-150 hover:text-primary"
                        >
                            Documentacion
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-muted-foreground underline transition-opacity duration-150 hover:text-primary"
                        >
                            Privacidad
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-muted-foreground underline transition-opacity duration-150 hover:text-primary"
                        >
                            Terminos
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-muted-foreground underline transition-opacity duration-150 hover:text-primary"
                        >
                            GitHub
                        </a>
                    </div>

                    <p className="font-mono text-[12px] text-muted-foreground">
                        © 2024 MANTIS Industrial — Open Source / MIT
                    </p>
                </div>
            </footer>
        </div>
    );
}
