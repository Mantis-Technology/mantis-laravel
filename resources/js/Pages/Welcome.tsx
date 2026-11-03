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
        <div ref={rootRef} className="min-h-svh bg-[#121414] text-[#e2e2e2]">
            <Head title="Gestión Inteligente de Activos y Mantenimiento Industrial" />

            <nav className="sticky top-0 z-50 mx-auto flex max-w-[1440px] items-center justify-between border-b border-[#333535] bg-[#121414] px-6 py-4 transition-all duration-300">
                <div className="flex items-center gap-4">
                    <img
                        alt="MANTIS Industrial Logo"
                        className="h-8 w-auto object-contain md:h-10"
                        src="/logo.png"
                    />
                </div>

                <div className="hidden items-center gap-8 md:flex">
                    <a
                        href="#features"
                        className="border-b-2 border-[#9cfb2b] pb-1 font-bold text-[#fefff4] transition-colors duration-200"
                    >
                        Features
                    </a>
                    <a
                        href="#opensource"
                        className="font-mono text-[14px] tracking-[0.05em] text-[#c0caaf] transition-colors duration-200 hover:text-[#9cfb2b]"
                    >
                        Open Source
                    </a>
                </div>

                <div className="flex items-center">
                    {auth.user ? (
                        <button
                            type="button"
                            onClick={logout}
                            className="bg-[#9bfa2a] px-6 py-2 font-mono text-[14px] tracking-[0.05em] text-[#191919] uppercase transition-colors duration-200 hover:bg-[#83dd00]"
                        >
                            Salir
                        </button>
                    ) : (
                        <Link
                            href="/access"
                            className="bg-[#9bfa2a] px-6 py-2 font-mono text-[14px] tracking-[0.05em] text-[#191919] uppercase transition-colors duration-200 hover:bg-[#83dd00]"
                        >
                            Acceder
                        </Link>
                    )}
                </div>
            </nav>

            <main className="flex-grow">
                <section className="mx-auto grid w-full max-w-[1440px] grid-cols-1 items-center gap-4 px-6 py-[120px] md:grid-cols-12 md:py-[180px]">
                    <div className="flex flex-col gap-8 md:col-span-8">
                        <h1 className="text-[48px] leading-[56px] font-extrabold tracking-[-0.02em] text-[#e2e2e2] uppercase">
                            <span className="hero-line block">
                                Gestión{' '}
                                <span className="text-[#9cfb2b]">
                                    Inteligente
                                </span>{' '}
                                de Activos y Mantenimiento Industrial
                            </span>
                        </h1>

                        <p className="hero-copy max-w-2xl text-[18px] leading-[28px] text-[#c0caaf]">
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
                                className="bg-[#9bfa2a] px-8 py-4 font-mono text-[14px] tracking-[0.05em] text-[#191919] uppercase transition-transform hover:scale-95"
                            >
                                Acceder a la Plataforma
                            </Link>
                            <a
                                href="#features"
                                className="border border-[#9bfa2a] px-8 py-4 font-mono text-[14px] tracking-[0.05em] text-[#9bfa2a] uppercase transition-colors hover:bg-[#1e3008]"
                            >
                                Documentación Técnica
                            </a>
                        </div>
                    </div>

                    <div className="hidden md:col-span-4 md:block">
                        <div className="hero-panel group relative aspect-square w-full overflow-hidden border border-[#333535] bg-[#1e2020]">
                            <div className="absolute top-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-[#9cfb2b] to-transparent opacity-50" />
                            <div className="absolute bottom-0 left-0 h-px w-full bg-gradient-to-r from-transparent via-[#9cfb2b] to-transparent opacity-50" />
                            <div className="absolute top-0 left-0 h-full w-px bg-gradient-to-b from-transparent via-[#9cfb2b] to-transparent opacity-50" />
                            <div className="absolute top-0 right-0 h-full w-px bg-gradient-to-b from-transparent via-[#9cfb2b] to-transparent opacity-50" />

                            <div className="absolute inset-0 flex items-center justify-center">
                                <QrCode
                                    className="h-[120px] w-[120px] text-[#9cfb2b] opacity-20 transition-opacity duration-500 group-hover:opacity-100"
                                    strokeWidth={1}
                                />
                            </div>

                            <span className="absolute right-4 bottom-4 font-mono text-[12px] tracking-[0.05em] text-[#9cfb2b] uppercase">
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
                        <div className="h-px flex-1 bg-[#333535]" />
                        <h2 className="text-[24px] leading-[32px] font-semibold tracking-wide text-[#e2e2e2] uppercase">
                            Módulos Core
                        </h2>
                        <div className="h-px flex-1 bg-[#333535]" />
                    </div>

                    <div className="modules-grid grid grid-cols-1 gap-4 md:auto-rows-[250px] md:grid-cols-12">
                        <article className="module-card group flex flex-col justify-between border border-[#333535] bg-[#2A2A2A] p-8 transition-all duration-300 hover:border-[#9cfb2b] hover:shadow-[0_0_10px_rgba(155,250,42,0.2)] md:col-span-8">
                            <div className="mb-4 flex items-start justify-between border-b border-[#333535] pb-4">
                                <h3 className="text-[32px] leading-[40px] font-bold tracking-[-0.01em] text-[#e2e2e2] transition-colors group-hover:text-[#9cfb2b]">
                                    Activos &amp; Sedes
                                </h3>
                                <Building2 className="h-8 w-8 text-[#c0caaf]" />
                            </div>
                            <div>
                                <p className="mb-4 text-[16px] leading-[24px] text-[#c0caaf]">
                                    Control exhaustivo de la jerarquía de
                                    activos a través de múltiples sedes.
                                    Arquitectura SaaS multi-tenant para aislar y
                                    gestionar datos de diferentes clientes con
                                    seguridad militar.
                                </p>
                                <div className="flex gap-2">
                                    <span className="bg-[#282a2b] px-2 py-1 font-mono text-[12px] text-[#e2e2e2]">
                                        Multi-tenant
                                    </span>
                                    <span className="bg-[#9bfa2a] px-2 py-1 font-mono text-[12px] text-[#191919]">
                                        QR Tracking
                                    </span>
                                </div>
                            </div>
                        </article>

                        <article className="module-card group flex flex-col justify-between border border-[#333535] bg-[#2A2A2A] p-8 transition-all duration-300 hover:border-[#9cfb2b] hover:shadow-[0_0_10px_rgba(155,250,42,0.2)] md:col-span-4">
                            <div className="mb-4 flex items-start justify-between border-b border-[#333535] pb-4">
                                <h3 className="text-[24px] leading-[32px] font-semibold text-[#e2e2e2] transition-colors group-hover:text-[#ffb4ab]">
                                    Incidencias
                                </h3>
                                <AlertTriangle className="h-8 w-8 text-[#ffb4ab]" />
                            </div>
                            <p className="text-[16px] leading-[24px] text-[#c0caaf]">
                                Reporte y resolución en tiempo real. Asignación
                                automática de técnicos y SLAs configurables.
                            </p>
                        </article>

                        <article className="module-card group flex flex-col justify-between border border-[#333535] bg-[#2A2A2A] p-8 transition-all duration-300 hover:border-[#9cfb2b] hover:shadow-[0_0_10px_rgba(155,250,42,0.2)] md:col-span-4">
                            <div className="mb-4 flex items-start justify-between border-b border-[#333535] pb-4">
                                <h3 className="text-[24px] leading-[32px] font-semibold text-[#e2e2e2] transition-colors group-hover:text-[#9cfb2b]">
                                    Predictiva
                                </h3>
                                <ChartLine className="h-8 w-8 text-[#9cfb2b]" />
                            </div>
                            <p className="text-[16px] leading-[24px] text-[#c0caaf]">
                                Algoritmos de análisis para anticipar fallos
                                antes de que ocurran, reduciendo el tiempo de
                                inactividad (Downtime).
                            </p>
                        </article>

                        <article
                            id="opensource"
                            className="module-card group flex flex-col justify-between border border-[#9cfb2b]/30 bg-[#1e2020] p-8 transition-all duration-300 hover:shadow-[0_0_10px_rgba(155,250,42,0.2)] md:col-span-8"
                        >
                            <div className="mb-4 flex items-start justify-between border-b border-[#9cfb2b]/30 pb-4">
                                <h3 className="text-[32px] leading-[40px] font-bold tracking-[-0.01em] text-[#e2e2e2] transition-colors group-hover:text-[#9cfb2b]">
                                    Open Source Base
                                </h3>
                                <Code2 className="h-8 w-8 text-[#c0caaf]" />
                            </div>
                            <div className="flex items-end justify-between">
                                <p className="max-w-md text-[16px] leading-[24px] text-[#c0caaf]">
                                    Construido sobre estándares abiertos.
                                    Auditable, extensible y sin vendor lock-in.
                                    Licencia MIT para el core de la plataforma.
                                </p>
                                <a
                                    href="#"
                                    className="flex items-center border border-[#9bfa2a] px-4 py-2 font-mono text-[14px] tracking-[0.05em] text-[#9bfa2a] uppercase transition-colors hover:bg-[#9bfa2a] hover:text-[#191919]"
                                >
                                    Ver Repositorio
                                </a>
                            </div>
                        </article>
                    </div>
                </section>
            </main>

            <footer className="border-t border-[#333535] bg-[#0c0f0f]">
                <div className="mx-auto flex w-full max-w-[1440px] flex-col items-center justify-between gap-8 px-6 py-8 md:flex-row">
                    <div className="font-mono text-[14px] font-bold text-[#e2e2e2]">
                        MANTIS
                    </div>

                    <div className="flex flex-wrap justify-center gap-6">
                        <a
                            href="#"
                            className="font-mono text-[12px] text-[#c0caaf] underline transition-opacity duration-150 hover:text-[#9cfb2b]"
                        >
                            Documentation
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-[#c0caaf] underline transition-opacity duration-150 hover:text-[#9cfb2b]"
                        >
                            Privacy Policy
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-[#c0caaf] underline transition-opacity duration-150 hover:text-[#9cfb2b]"
                        >
                            Terms of Service
                        </a>
                        <a
                            href="#"
                            className="font-mono text-[12px] text-[#c0caaf] underline transition-opacity duration-150 hover:text-[#9cfb2b]"
                        >
                            GitHub
                        </a>
                    </div>

                    <p className="text-sm text-[#c0caaf]">
                        © 2024 MANTIS Industrial. Open Source under MIT License.
                    </p>
                </div>
            </footer>
        </div>
    );
}
