import { Head, Link, router, usePage } from '@inertiajs/react';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import {
    AlertTriangle,
    Building2,
    ChartLine,
    Code2,
    QrCode,
} from 'lucide-react';
import { useCallback, useEffect, useRef } from 'react';

gsap.registerPlugin(ScrollTrigger);

type User = {
    id: number;
    name: string;
    email: string;
};

type PageProps = {
    auth: {
        user: User | null;
    };
};

export default function Welcome() {
    const { auth } = usePage<PageProps>().props;

    const rootRef = useRef<HTMLDivElement>(null);

    const logout = useCallback(() => {
        router.post('/logout');
    }, []);

    useEffect(() => {
        const root = rootRef.current;

        if (!root) {
            return;
        }

        const prefersReduced = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        const ctx = gsap.context(() => {
            const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

            tl.fromTo(
                '.hero-line',
                { opacity: 0, y: 22 },
                { opacity: 1, y: 0, duration: 0.7, stagger: 0.12 },
            )
                .fromTo(
                    '.hero-copy',
                    { opacity: 0, y: 16 },
                    { opacity: 1, y: 0, duration: 0.6 },
                    '-=0.4',
                )
                .fromTo(
                    '.hero-actions',
                    { opacity: 0, y: 14 },
                    { opacity: 1, y: 0, duration: 0.5 },
                    '-=0.3',
                )
                .fromTo(
                    '.hero-panel',
                    { opacity: 0, scale: 0.96 },
                    { opacity: 1, scale: 1, duration: 0.7 },
                    '-=0.5',
                );

            gsap.fromTo(
                '.module-card',
                { opacity: 0, y: 30 },
                {
                    opacity: 1,
                    y: 0,
                    duration: 0.6,
                    stagger: 0.1,
                    scrollTrigger: {
                        trigger: '.modules-grid',
                        start: 'top 82%',
                    },
                },
            );
        }, root);

        const onMouseMove = (event: MouseEvent) => {
            if (prefersReduced) {
                return;
            }

            const moveX = (event.clientX / window.innerWidth - 0.5) * 5;
            const moveY = (event.clientY / window.innerHeight - 0.5) * 5;

            root.style.backgroundPosition = `${moveX}px ${moveY}px`;
        };

        window.addEventListener('mousemove', onMouseMove);

        return () => {
            ctx.revert();
            window.removeEventListener('mousemove', onMouseMove);
        };
    }, []);

    return (
        <div ref={rootRef} className="min-h-svh bg-background text-foreground">
            <Head title="Gestión Inteligente de Activos y Mantenimiento Industrial" />

            <div className="sticky top-0 z-50 w-full border-b border-white/10 bg-linear-to-r from-[#2c2d2e] to-[#3a3b3c] shadow-md">
                <nav className="mx-auto flex w-full max-w-[1440px] items-center justify-between px-6 py-4">
                <div className="flex items-center gap-4">
                    <img
                        alt="MANTIS Industrial Logo"
                        className="h-9 w-auto object-contain md:h-11"
                        src="/logo.png"
                    />
                </div>

                <div className="hidden items-center gap-8 md:flex">
                    <a
                        href="#features"
                        className="border-b-2 border-primary pb-1 font-bold text-white transition-colors duration-200"
                    >
                        Features
                    </a>
                    <a
                        href="#opensource"
                        className="font-mono text-[14px] tracking-[0.05em] text-[#C9CBCC] transition-colors duration-200 hover:text-primary"
                    >
                        Open Source
                    </a>
                </div>

                <div className="flex items-center">
                    {auth.user ? (
                        <button
                            type="button"
                            onClick={logout}
                            className="bg-primary px-6 py-2 font-mono text-[14px] tracking-[0.05em] text-primary-foreground uppercase transition-colors duration-200 hover:bg-primary/85"
                        >
                            Salir
                        </button>
                    ) : (
                        <Link
                            href="/access"
                            className="bg-primary px-6 py-2 font-mono text-[14px] tracking-[0.05em] text-primary-foreground uppercase transition-colors duration-200 hover:bg-primary/85"
                        >
                            Acceder
                        </Link>
                    )}
                </div>
            </nav>
            </div>

            <main className="flex-grow">
                <section className="mx-auto grid w-full max-w-[1440px] grid-cols-1 items-center gap-4 px-6 py-[120px] md:grid-cols-12 md:py-[180px]">
                    <div className="flex flex-col gap-8 md:col-span-8">
                        <h1 className="text-[48px] leading-[56px] font-extrabold tracking-[-0.02em] text-foreground uppercase">
                            <span className="hero-line block">
                                Gestión{' '}
                                <span className="text-primary">
                                    Inteligente
                                </span>{' '}
                                de Activos y Mantenimiento Industrial
                            </span>
                        </h1>

                        <p className="hero-copy max-w-2xl text-[18px] leading-[28px] text-muted-foreground">
                            Plataforma SaaS multi-tenant diseñada para el
                            control total del ciclo de vida de los activos.
                            Identificación instantánea mediante códigos QR,
                            analítica predictiva y gestión de incidencias en
                            tiempo real. Ingeniería de alta precisión para
                            entornos industriales exigentes.
                        </p>

                        <div className="hero-actions mt-4 flex flex-col gap-4 sm:flex-row">
                            <Link
                                href="/access"
                                className="bg-primary px-8 py-4 font-mono text-[14px] tracking-[0.05em] text-primary-foreground uppercase transition-transform hover:scale-95"
                            >
                                Acceder a la Plataforma
                            </Link>
                            <a
                                href="#features"
                                className="border border-primary px-8 py-4 font-mono text-[14px] tracking-[0.05em] text-accent-foreground dark:text-primary uppercase transition-colors hover:bg-accent"
                            >
                                Documentación Técnica
                            </a>
                        </div>
                    </div>

                    <div className="hidden md:col-span-4 md:block">
                        <div className="hero-panel group relative aspect-square w-full overflow-hidden border border-border bg-card">
                            <div className="absolute top-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-primary to-transparent opacity-50" />
                            <div className="absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-primary to-transparent opacity-50" />
                            <div className="absolute top-0 left-0 h-full w-px bg-gradient-to-b from-transparent via-primary to-transparent opacity-50" />
                            <div className="absolute top-0 right-0 h-full w-px bg-gradient-to-b from-transparent via-primary to-transparent opacity-50" />

                            <div className="absolute inset-0 flex items-center justify-center">
                                <QrCode
                                    className="h-[120px] w-[120px] text-primary opacity-20 transition-opacity duration-500 group-hover:opacity-100"
                                    strokeWidth={1}
                                />
                            </div>

                            <span className="absolute right-4 bottom-4 font-mono text-[12px] tracking-[0.05em] text-accent-foreground dark:text-primary uppercase">
                                System_Ready :: v2.4.1
                            </span>
                        </div>
                    </div>
                </section>

                <section
                    id="features"
                    className="mx-auto mb-[100px] w-full max-w-[1440px] px-6 py-8"
                >
                    <div className="mb-12 flex items-center gap-4">
                        <div className="h-px flex-1 bg-border" />
                        <h2 className="text-[24px] leading-[32px] font-semibold tracking-wide text-foreground uppercase">
                            Módulos Core
                        </h2>
                        <div className="h-px flex-1 bg-border" />
                    </div>

                    <div className="modules-grid grid grid-cols-1 gap-4 md:auto-rows-[250px] md:grid-cols-12">
                        <article className="module-card group flex flex-col justify-between border border-border bg-card p-8 transition-all duration-300 hover:border-primary hover:shadow-md md:col-span-8">
                            <div className="mb-4 flex items-start justify-between border-b border-border pb-4">
                                <h3 className="text-[32px] leading-[40px] font-bold tracking-[-0.01em] text-foreground transition-colors group-hover:text-primary">
                                    Activos &amp; Sedes
                                </h3>
                                <Building2 className="h-8 w-8 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="mb-4 text-[16px] leading-[24px] text-muted-foreground">
                                    Control exhaustivo de la jerarquía de
                                    activos a través de múltiples sedes.
                                    Arquitectura SaaS multi-tenant para aislar y
                                    gestionar datos de diferentes clientes con
                                    seguridad militar.
                                </p>
                                <div className="flex gap-2">
                                    <span className="bg-secondary px-2 py-1 font-mono text-[12px] text-foreground">
                                        Multi-tenant
                                    </span>
                                    <span className="bg-primary px-2 py-1 font-mono text-[12px] text-primary-foreground">
                                        QR Tracking
                                    </span>
                                </div>
                            </div>
                        </article>

                        <article className="module-card group flex flex-col justify-between border border-border bg-card p-8 transition-all duration-300 hover:border-primary hover:shadow-md md:col-span-4">
                            <div className="mb-4 flex items-start justify-between border-b border-border pb-4">
                                <h3 className="text-[24px] leading-[32px] font-semibold text-foreground transition-colors group-hover:text-destructive">
                                    Incidencias
                                </h3>
                                <AlertTriangle className="h-8 w-8 text-destructive" />
                            </div>
                            <p className="text-[16px] leading-[24px] text-muted-foreground">
                                Reporte y resolución en tiempo real. Asignación
                                automática de técnicos y SLAs configurables.
                            </p>
                        </article>

                        <article className="module-card group flex flex-col justify-between border border-border bg-card p-8 transition-all duration-300 hover:border-primary hover:shadow-md md:col-span-4">
                            <div className="mb-4 flex items-start justify-between border-b border-border pb-4">
                                <h3 className="text-[24px] leading-[32px] font-semibold text-foreground transition-colors group-hover:text-primary">
                                    Predictiva
                                </h3>
                                <ChartLine className="h-8 w-8 text-primary" />
                            </div>
                            <p className="text-[16px] leading-[24px] text-muted-foreground">
                                Algoritmos de análisis para anticipar fallos
                                antes de que ocurran, reduciendo el tiempo de
                                inactividad (Downtime).
                            </p>
                        </article>

                        <article
                            id="opensource"
                            className="module-card group flex flex-col justify-between border border-primary/30 bg-card p-8 transition-all duration-300 hover:shadow-md md:col-span-8"
                        >
                            <div className="mb-4 flex items-start justify-between border-b border-primary/30 pb-4">
                                <h3 className="text-[32px] leading-[40px] font-bold tracking-[-0.01em] text-foreground transition-colors group-hover:text-primary">
                                    Open Source Base
                                </h3>
                                <Code2 className="h-8 w-8 text-muted-foreground" />
                            </div>
                            <div className="flex items-end justify-between">
                                <p className="max-w-md text-[16px] leading-[24px] text-muted-foreground">
                                    Construido sobre estándares abiertos.
                                    Auditable, extensible y sin vendor lock-in.
                                    Licencia MIT para el core de la plataforma.
                                </p>
                                <a
                                    href="#"
                                    className="flex items-center border border-primary px-4 py-2 font-mono text-[14px] tracking-[0.05em] text-accent-foreground dark:text-primary uppercase transition-colors hover:bg-primary hover:text-primary-foreground"
                                >
                                    Ver Repositorio
                                </a>
                            </div>
                        </article>
                    </div>
                </section>
            </main>

            <footer className="border-t border-border bg-muted">
                <div className="mx-auto flex w-full max-w-[1440px] flex-col items-center justify-between gap-8 px-6 py-8 md:flex-row">
                    <div className="font-mono text-[14px] font-bold text-foreground">
                        MANTIS
                    </div>

                    <div className="flex flex-wrap justify-center gap-6">
                        <a
                            href="#"
                            className="font-mono text-[12px] text-muted-foreground underline transition-opacity duration-150 hover:text-primary"
                        >
                            Documentation
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-muted-foreground underline transition-opacity duration-150 hover:text-primary"
                        >
                            Privacy Policy
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-muted-foreground underline transition-opacity duration-150 hover:text-primary"
                        >
                            Terms of Service
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-muted-foreground underline transition-opacity duration-150 hover:text-primary"
                        >
                            GitHub
                        </a>
                    </div>

                    <p className="text-sm text-muted-foreground">
                        © 2024 MANTIS Industrial. Open Source under MIT License.
                    </p>
                </div>
            </footer>
        </div>
    );
}
