import { Head, Link } from '@inertiajs/react';
import { gsap } from 'gsap';
import { useCallback, useEffect, useRef, useState } from 'react';

type Props = {
    domainBase: string;
    scheme: string;
    port: string;
};

export default function CompanyPortal({ domainBase, scheme, port }: Props) {
    const rootRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const [slug, setSlug] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [checking, setChecking] = useState(false);

    const suffix = `.${domainBase}${port}`;

    useEffect(() => {
        const root = rootRef.current;

        if (!root) {
            return;
        }

        const ctx = gsap.context(() => {
            const prefersReduced = window.matchMedia(
                '(prefers-reduced-motion: reduce)',
            ).matches;
            const duration = prefersReduced ? 0 : 0.6;

            gsap.fromTo(
                '.portal-card',
                { opacity: 0, y: 18 },
                { opacity: 1, y: 0, duration, ease: 'power3.out' },
            );
        }, root);

        return () => ctx.revert();
    }, []);

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const clean = slug.trim().toLowerCase();

        if (!clean) {
            setError('Ingresa el dominio de tu empresa.');
            inputRef.current?.focus();

            return;
        }

        if (!/^[a-z0-9][a-z0-9-]*[a-z0-9]$/.test(clean)) {
            setError(
                'Solo letras, números y guiones. Sin espacios ni símbolos.',
            );
            inputRef.current?.focus();

            return;
        }

        setError(null);

        verifyDomain(clean);
    };

    const verifyDomain = useCallback(
        async (clean: string) => {
            setChecking(true);

            try {
                const verifyUrl = `${scheme}://${domainBase}${port}/portal/verify/${encodeURIComponent(clean)}`;
                const response = await fetch(verifyUrl);

                if (!response.ok) {
                    throw new Error('Verification failed');
                }

                const data = (await response.json()) as { available: boolean };

                if (!data.available) {
                    setChecking(false);
                    setError('El dominio no existe o no está disponible.');
                    inputRef.current?.focus();

                    return;
                }

                const url = `${scheme}://${clean}.${domainBase}${port}/login`;
                window.location.assign(url);
            } catch {
                setChecking(false);
                setError('No se pudo verificar el dominio. Intenta de nuevo.');
            }
        },
        [domainBase, port, scheme],
    );

    return (
        <div
            ref={rootRef}
            className="flex min-h-svh flex-col bg-background text-foreground"
        >
            <Head title="Portal de Empresas" />

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
                    Tenant_OPS / Login
                </span>
            </nav>
            </div>

            <main className="flex flex-1 items-center justify-center px-6 py-16">
                <div className="portal-card relative w-full max-w-md overflow-hidden border border-border bg-card">
                    <div className="absolute top-0 left-0 h-1 w-full bg-primary" />

                    <div className="p-8 sm:p-10">
                        <div className="mb-8 flex flex-col items-center text-center">
                            <div className="mb-6 flex h-14 w-14 items-center justify-center border border-border bg-background">
                                <svg
                                    viewBox="0 0 24 24"
                                    className="h-7 w-7 text-primary"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="1"
                                    aria-hidden="true"
                                >
                                    <path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM18 14h3v3h-3zM14 18h3v3h-3zM18 18h3v3h-3z" />
                                </svg>
                            </div>

                            <h1 className="mb-3 text-[24px] leading-[32px] font-semibold text-foreground uppercase">
                                Ingresa el dominio de tu empresa
                            </h1>
                            <p className="max-w-sm text-[16px] leading-[24px] text-muted-foreground">
                                Serás redirigido a tu portal de login
                                personalizado.
                            </p>
                        </div>

                        <form
                            onSubmit={handleSubmit}
                            noValidate
                            className="space-y-6"
                        >
                            <div className="space-y-2">
                                <label
                                    htmlFor="company-domain"
                                    className="block font-mono text-[12px] tracking-[0.05em] text-muted-foreground uppercase"
                                >
                                    Dominio empresarial
                                </label>

                                <div className="flex items-stretch border border-border bg-background transition-colors duration-200 focus-within:border-primary">
                                    <input
                                        ref={inputRef}
                                        id="company-domain"
                                        name="domain"
                                        type="text"
                                        value={slug}
                                        onChange={(event) =>
                                            setSlug(event.target.value)
                                        }
                                        placeholder="tu-empresa"
                                        autoComplete="off"
                                        spellCheck={false}
                                        className="w-full bg-transparent px-4 py-3 text-base text-foreground outline-none placeholder:text-muted-foreground/50"
                                    />
                                    <div className="flex items-center border-l border-border bg-card px-4 select-none">
                                        <span className="font-mono text-sm whitespace-nowrap text-muted-foreground">
                                            {suffix}
                                        </span>
                                    </div>
                                </div>

                                {error && (
                                    <p
                                        role="alert"
                                        className="font-mono text-[12px] tracking-[0.05em] text-destructive uppercase"
                                    >
                                        {error}
                                    </p>
                                )}
                            </div>

                            <div className="flex flex-col gap-4 pt-2">
                                <button
                                    type="submit"
                                    disabled={checking}
                                    className="flex w-full items-center justify-center gap-2 bg-primary px-6 py-4 font-mono text-[14px] font-semibold tracking-[0.05em] text-primary-foreground uppercase transition-colors hover:bg-primary/85 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {checking
                                        ? 'Verificando...'
                                        : 'Validar y Continuar'}
                                    {!checking && (
                                        <span aria-hidden="true">→</span>
                                    )}
                                </button>

                                <Link
                                    href="/access"
                                    className="flex w-full items-center justify-center gap-2 py-2 font-mono text-[14px] tracking-[0.05em] text-accent-foreground dark:text-primary uppercase transition-colors hover:underline"
                                >
                                    <span aria-hidden="true">←</span>
                                    Volver
                                </Link>
                            </div>
                        </form>
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
