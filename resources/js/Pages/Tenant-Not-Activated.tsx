import { Head, Link } from '@inertiajs/react';
import { gsap } from 'gsap';
import { useEffect, useRef } from 'react';

type PageProps = {
    tenantName: string;
    centralUrl: string;
};

export default function TenantNotActivated({
    tenantName,
    centralUrl,
}: PageProps) {
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const root = rootRef.current;

        if (!root) {
            return;
        }

        const ctx = gsap.context(() => {
            gsap.fromTo(
                '.status-panel',
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
            <Head title="Empresa no activada" />

            <nav className="sticky top-0 z-50 mx-auto flex w-full max-w-[1440px] items-center justify-between border-b border-[#333535] bg-[#121414] px-6 py-4">
                <Link href="/" className="flex items-center gap-4">
                    <img
                        alt="MANTIS Industrial Logo"
                        className="h-8 w-auto object-contain md:h-10"
                        src="/logo.png"
                    />
                </Link>
            </nav>

            <main className="mx-auto flex w-full max-w-[1440px] flex-1 items-center px-6 py-16">
                <div className="status-panel mx-auto w-full max-w-xl border border-[#333535] bg-[#2A2A2A] p-8 sm:p-10">
                    <p className="mb-4 inline-flex border border-[#ffb4ab]/40 bg-[#ffb4ab]/10 px-3 py-1 font-mono text-[12px] tracking-[0.05em] text-[#ffb4ab] uppercase">
                        System_Status :: Pending
                    </p>

                    <h1 className="text-[24px] leading-[32px] font-bold">
                        Su empresa aún no está activada
                    </h1>

                    <p className="mt-3 text-[16px] leading-[24px] text-[#c0caaf]">
                        <span className="text-[#e2e2e2]">{tenantName}</span> ha
                        sido registrada en la plataforma MANTIS, pero todavía no
                        ha sido activada por nuestro equipo.
                    </p>

                    <p className="mt-3 text-[16px] leading-[24px] text-[#c0caaf]">
                        El acceso estará disponible en cuanto su empresa sea
                        activada. Si lo desea, puede consultar el estado desde
                        el portal de acceso de empresas.
                    </p>

                    <a
                        href={centralUrl}
                        className="mt-8 inline-block w-full bg-[#9bfa2a] px-6 py-3 text-center font-mono text-[14px] tracking-[0.05em] text-[#191919] uppercase transition-colors hover:bg-[#83dd00]"
                    >
                        Volver
                    </a>
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
