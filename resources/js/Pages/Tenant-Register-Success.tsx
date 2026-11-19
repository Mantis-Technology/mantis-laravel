import { Head, Link } from '@inertiajs/react';
import { gsap } from 'gsap';
import { useEffect, useRef } from 'react';

export default function TenantRegisterSuccess() {
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const root = rootRef.current;

        if (!root) {
            return;
        }

        const ctx = gsap.context(() => {
            gsap.fromTo(
                '.success-card',
                { opacity: 0, y: 24 },
                { opacity: 1, y: 0, duration: 0.6, ease: 'power3.out' },
            );
        }, root);

        return () => ctx.revert();
    }, []);

    return (
        <div
            ref={rootRef}
            className="flex min-h-svh flex-col bg-background text-foreground"
        >
            <Head title="Solicitud Recibida" />

            <div className="sticky top-0 z-50 w-full border-b border-white/10 bg-linear-to-r from-[#2c2d2e] to-[#3a3b3c] shadow-md">
                <nav className="mx-auto flex w-full max-w-[1440px] items-center justify-between px-6 py-4">
                <Link href="/" className="flex items-center gap-4">
                    <img
                        alt="MANTIS Industrial Logo"
                        className="h-9 w-auto object-contain md:h-11"
                        src="/logo.png"
                    />
                </Link>

                <Link
                    href="/access"
                    className="bg-primary px-6 py-2 font-mono text-[14px] tracking-[0.05em] text-primary-foreground uppercase transition-colors hover:bg-primary/85"
                >
                    Acceder
                </Link>
            </nav>
            </div>

            <main className="mx-auto flex w-full max-w-[1440px] flex-1 items-center justify-center px-6 py-20">
                <div className="success-card w-full max-w-xl border border-border bg-card px-8 py-12 text-center sm:px-10">
                    <div className="mx-auto mb-8 flex h-16 w-16 items-center justify-center border border-primary bg-background">
                        <svg
                            viewBox="0 0 24 24"
                            className="h-8 w-8 text-primary"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            aria-hidden="true"
                        >
                            <path strokeLinecap="square" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <p className="mb-2 font-mono text-[12px] tracking-[0.05em] text-accent-foreground dark:text-primary uppercase">
                        Solicitud recibida
                    </p>
                    <h1 className="mb-4 text-[32px] leading-[40px] font-bold tracking-[-0.01em] text-foreground uppercase">
                        Empresa en revisión
                    </h1>
                    <p className="mb-2 text-[16px] leading-[24px] text-muted-foreground">
                        Su solicitud de registro se ha recibido correctamente.
                        La empresa quedará en estado pendiente hasta que sea
                        revisada y activada por el equipo de MANTIS.
                    </p>
                    <p className="mb-10 font-mono text-[12px] tracking-[0.05em] text-accent-foreground dark:text-primary">
                        Status :: Pending
                    </p>

                    <div className="flex flex-col justify-center gap-4 sm:flex-row">
                        <Link
                            href="/"
                            className="flex items-center justify-center bg-primary px-8 py-3 font-mono text-[14px] tracking-[0.05em] text-primary-foreground uppercase transition-colors hover:bg-primary/85"
                        >
                            Volver al inicio
                        </Link>
                        <Link
                            href="/access"
                            className="flex items-center justify-center border border-primary px-8 py-3 font-mono text-[14px] tracking-[0.05em] text-accent-foreground dark:text-primary uppercase transition-colors hover:bg-primary hover:text-primary-foreground"
                        >
                            Ir a Acceso
                        </Link>
                    </div>
                </div>
            </main>

            <footer className="border-t border-border bg-muted">
                <div className="mx-auto flex w-full max-w-[1440px] flex-col items-center justify-between gap-4 px-6 py-6 md:flex-row">
                    <div className="font-mono text-[12px] font-bold text-foreground">
                        MANTIS
                    </div>
                    <p className="font-mono text-[12px] text-muted-foreground">
                        © 2024 MANTIS Industrial — Open Source / MIT
                    </p>
                </div>
            </footer>
        </div>
    );
}
