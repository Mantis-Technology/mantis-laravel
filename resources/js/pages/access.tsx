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
            className="flex min-h-svh flex-col bg-[#121414] text-[#e2e2e2]"
        >
            <Head title="Selección de Acceso" />

            <nav className="sticky top-0 z-50 mx-auto flex w-full max-w-[1440px] items-center justify-between border-b border-[#333535] bg-[#121414] px-6 py-4">
                <Link href="/" className="flex items-center gap-4">
                    <img
                        alt="MANTIS Industrial Logo"
                        className="h-8 w-auto object-contain md:h-10"
                        src="/logo.png"
                    />
                </Link>

                <span className="hidden font-mono text-[12px] tracking-[0.05em] text-[#c0caaf] uppercase md:inline">
                    Access_OPS / Portal Select
                </span>
            </nav>

            <main className="mx-auto flex w-full max-w-[1440px] flex-1 flex-col items-center justify-center px-6 py-20">
                <div className="mb-12 max-w-2xl text-center">
                    <p className="access-eyebrow mb-5 font-mono text-[14px] tracking-[0.05em] text-[#9cfb2b] uppercase">
                        Seleccione su destino
                    </p>

                    <h1 className="access-title text-[48px] leading-[56px] font-extrabold tracking-[-0.02em] text-[#e2e2e2] uppercase">
                        Portal de acceso
                    </h1>

                    <p className="access-sub mx-auto mt-5 max-w-xl text-[18px] leading-[28px] text-[#c0caaf]">
                        Elija su destino para continuar con la autenticación en
                        el ecosistema industrial MANTIS.
                    </p>
                </div>

                <div className="grid w-full max-w-5xl grid-cols-1 gap-4 md:grid-cols-2">
                    {PORTALS.map((portal) => {
                        const href = portal.external
                            ? (adminUrl ?? portal.href)
                            : portal.href;

                        const content = (
                            <>
                                <div className="mb-6 flex items-start justify-between border-b border-[#333535] pb-4">
                                    <h2 className="text-[24px] leading-[32px] font-semibold text-[#e2e2e2] transition-colors group-hover:text-[#9cfb2b]">
                                        {portal.title}
                                    </h2>
                                    {portal.isPrimary ? (
                                        <Building2 className="h-8 w-8 text-[#c0caaf]" />
                                    ) : (
                                        <QrCode className="h-8 w-8 text-[#c0caaf]" />
                                    )}
                                </div>

                                <p className="mb-6 text-[16px] leading-[24px] text-[#c0caaf]">
                                    {portal.description}
                                </p>

                                <div className="mt-auto flex items-center justify-between border-t border-[#333535] pt-6">
                                    <span className="font-mono text-[12px] text-[#c0caaf]">
                                        {portal.scope}
                                    </span>
                                    <span
                                        className={`inline-flex items-center gap-2 px-4 py-2 font-mono text-[14px] tracking-[0.05em] uppercase transition-colors ${
                                            portal.isPrimary
                                                ? 'bg-[#9bfa2a] text-[#191919] hover:bg-[#83dd00]'
                                                : 'border border-[#9bfa2a] text-[#9bfa2a] hover:bg-[#9bfa2a] hover:text-[#191919]'
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

                        const cardClasses =
                            'portal-card group flex flex-col border border-[#333535] bg-[#2A2A2A] p-8 transition-all duration-300 hover:border-[#9cfb2b] hover:shadow-[0_0_10px_rgba(155,250,42,0.2)] sm:p-10';

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
                    <div className="h-px flex-1 bg-[#333535]" />
                    <p className="font-mono text-[12px] tracking-[0.05em] text-[#c0caaf] uppercase">
                        ¿Aún no es cliente?
                    </p>
                    <div className="h-px flex-1 bg-[#333535]" />
                </div>

                <Link
                    href="/register/company"
                    className="mt-6 inline-flex items-center gap-2 border border-[#9cfb2b] px-6 py-3 font-mono text-[14px] tracking-[0.05em] text-[#9cfb2b] uppercase transition-colors hover:bg-[#9cfb2b] hover:text-[#191919]"
                >
                    Registrar mi empresa
                    <span aria-hidden="true">→</span>
                </Link>
            </main>

            <footer className="border-t border-[#333535] bg-[#0c0f0f]">
                <div className="mx-auto flex w-full max-w-[1440px] flex-col items-center justify-between gap-6 px-6 py-8 md:flex-row">
                    <div className="font-mono text-[14px] font-bold text-[#e2e2e2]">
                        MANTIS
                    </div>

                    <div className="flex flex-wrap justify-center gap-6">
                        <a
                            href="#"
                            className="font-mono text-[12px] text-[#c0caaf] underline transition-opacity duration-150 hover:text-[#9cfb2b]"
                        >
                            Documentacion
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-[#c0caaf] underline transition-opacity duration-150 hover:text-[#9cfb2b]"
                        >
                            Privacidad
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-[#c0caaf] underline transition-opacity duration-150 hover:text-[#9cfb2b]"
                        >
                            Terminos
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-[#c0caaf] underline transition-opacity duration-150 hover:text-[#9cfb2b]"
                        >
                            GitHub
                        </a>
                    </div>

                    <p className="font-mono text-[12px] text-[#c0caaf]">
                        © 2024 MANTIS Industrial — Open Source / MIT
                    </p>
                </div>
            </footer>
        </div>
    );
}
