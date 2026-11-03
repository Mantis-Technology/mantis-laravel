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
            className="flex min-h-svh flex-col bg-[#121414] text-[#e2e2e2]"
        >
            <Head title="Solicitud Recibida" />

            <nav className="sticky top-0 z-50 mx-auto flex w-full max-w-[1440px] items-center justify-between border-b border-[#333535] bg-[#121414] px-6 py-4">
                <Link href="/" className="flex items-center gap-4">
                    <img
                        alt="MANTIS Industrial Logo"
                        className="h-8 w-auto object-contain md:h-10"
                        src="/logo.png"
                    />
                </Link>

                <Link
                    href="/access"
                    className="bg-[#9bfa2a] px-6 py-2 font-mono text-[14px] tracking-[0.05em] text-[#191919] uppercase transition-colors hover:bg-[#83dd00]"
                >
                    Acceder
                </Link>
            </nav>

            <main className="mx-auto flex w-full max-w-[1440px] flex-1 items-center justify-center px-6 py-20">
                <div className="success-card w-full max-w-xl border border-[#333535] bg-[#2A2A2A] px-8 py-12 text-center sm:px-10">
                    <div className="mx-auto mb-8 flex h-16 w-16 items-center justify-center border border-[#9cfb2b] bg-[#121414]">
                        <svg
                            viewBox="0 0 24 24"
                            className="h-8 w-8"
                            fill="none"
                            stroke="#9cfb2b"
                            strokeWidth="1.5"
                            aria-hidden="true"
                        >
                            <path strokeLinecap="square" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <p className="mb-2 font-mono text-[12px] tracking-[0.05em] text-[#9cfb2b] uppercase">
                        Solicitud recibida
                    </p>
                    <h1 className="mb-4 text-[32px] leading-[40px] font-bold tracking-[-0.01em] text-[#e2e2e2] uppercase">
                        Empresa en revisión
                    </h1>
                    <p className="mb-2 text-[16px] leading-[24px] text-[#c0caaf]">
                        Su solicitud de registro se ha recibido correctamente.
                        La empresa quedará en estado pendiente hasta que sea
                        revisada y activada por el equipo de MANTIS.
                    </p>
                    <p className="mb-10 font-mono text-[12px] tracking-[0.05em] text-[#9cfb2b]">
                        Status :: Pending
                    </p>

                    <div className="flex flex-col justify-center gap-4 sm:flex-row">
                        <Link
                            href="/"
                            className="flex items-center justify-center bg-[#9bfa2a] px-8 py-3 font-mono text-[14px] tracking-[0.05em] text-[#191919] uppercase transition-colors hover:bg-[#83dd00]"
                        >
                            Volver al inicio
                        </Link>
                        <Link
                            href="/access"
                            className="flex items-center justify-center border border-[#9cfb2b] px-8 py-3 font-mono text-[14px] tracking-[0.05em] text-[#9cfb2b] uppercase transition-colors hover:bg-[#9bfa2a] hover:text-[#191919]"
                        >
                            Ir a Acceso
                        </Link>
                    </div>
                </div>
            </main>

            <footer className="border-t border-[#333535] bg-[#0c0f0f]">
                <div className="mx-auto flex w-full max-w-[1440px] flex-col items-center justify-between gap-4 px-6 py-6 md:flex-row">
                    <div className="font-mono text-[12px] font-bold text-[#e2e2e2]">
                        MANTIS
                    </div>
                    <p className="font-mono text-[12px] text-[#c0caaf]">
                        © 2024 MANTIS Industrial — Open Source / MIT
                    </p>
                </div>
            </footer>
        </div>
    );
}
