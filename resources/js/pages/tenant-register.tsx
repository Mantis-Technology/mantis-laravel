import { Head, Link, useForm } from '@inertiajs/react';
import { gsap } from 'gsap';
import { useEffect, useRef, useState } from 'react';

type RegistrationForm = {
    name: string;
    tax_id: string;
    contact_email: string;
    phone: string;
    contact_name: string;
    address: string;
    city: string;
    province: string;
    postal_code: string;
    country: string;
    logo: File | null;
};

export default function TenantRegister() {
    const rootRef = useRef<HTMLDivElement>(null);
    const logoInputRef = useRef<HTMLInputElement>(null);
    const [logoPreview, setLogoPreview] = useState<string | null>(null);

    const { data, setData, post, processing, errors } =
        useForm<RegistrationForm>({
            name: '',
            tax_id: '',
            contact_email: '',
            phone: '',
            contact_name: '',
            address: '',
            city: '',
            province: '',
            postal_code: '',
            country: '',
            logo: null,
        });

    useEffect(() => {
        const root = rootRef.current;

        if (!root) {
            return;
        }

        const ctx = gsap.context(() => {
            gsap.fromTo(
                '.register-card',
                { opacity: 0, y: 24 },
                { opacity: 1, y: 0, duration: 0.6, ease: 'power3.out' },
            );
        }, root);

        return () => ctx.revert();
    }, []);

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post('/register/company');
    };

    const handleLogoChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        setData('logo', file);

        if (logoPreview !== null) {
            URL.revokeObjectURL(logoPreview);
        }

        setLogoPreview(file ? URL.createObjectURL(file) : null);
    };

    const inputClass = (hasError: boolean = false): string =>
        `w-full border bg-[#121414] px-4 py-3 text-[16px] leading-[24px] text-[#e2e2e2] outline-none transition-colors placeholder:text-[#c0caaf]/50 focus:border-[#9cfb2b] ${
            hasError ? 'border-[#ffb4ab]' : 'border-[#333535]'
        }`;

    const labelClass =
        'mb-1 block font-mono text-[12px] tracking-[0.05em] text-[#c0caaf] uppercase';

    const errorClass = 'mt-1 font-mono text-[12px] text-[#ffb4ab]';

    return (
        <div
            ref={rootRef}
            className="flex min-h-svh flex-col bg-[#121414] text-[#e2e2e2]"
        >
            <Head title="Registro de Empresas" />

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

            <main className="mx-auto w-full max-w-[1440px] flex-1 px-6 py-16">
                <div className="register-card mx-auto max-w-3xl border border-[#333535] bg-[#2A2A2A]">
                    <div className="border-b border-[#333535] px-8 py-8 sm:px-10">
                        <p className="mb-2 font-mono text-[12px] tracking-[0.05em] text-[#9cfb2b] uppercase">
                            Onboarding / Empresas
                        </p>
                        <h1 className="text-[32px] leading-[40px] font-bold tracking-[-0.01em] text-[#e2e2e2] uppercase">
                            Regístrese como empresa
                        </h1>
                        <p className="mt-3 max-w-2xl text-[16px] leading-[24px] text-[#c0caaf]">
                            Complete la información legal y de contacto. Su
                            solicitud quedará en estado pendiente y será
                            revisada antes de su activación.
                        </p>
                    </div>

                    <form
                        onSubmit={submit}
                        noValidate
                        className="grid grid-cols-1 gap-x-6 gap-y-6 px-8 py-8 sm:grid-cols-2 sm:px-10"
                    >
                        <div className="sm:col-span-2">
                            <span className={labelClass}>
                                Logo de la empresa
                            </span>
                            <button
                                type="button"
                                onClick={() => logoInputRef.current?.click()}
                                className="flex w-full items-center justify-center gap-3 border border-dashed border-[#333535] bg-[#121414] px-4 py-6 transition-colors hover:border-[#9cfb2b]"
                            >
                                {logoPreview ? (
                                    <img
                                        src={logoPreview}
                                        alt="Vista previa del logo"
                                        className="max-h-20 object-contain"
                                    />
                                ) : (
                                    <span className="font-mono text-[14px] tracking-[0.05em] text-[#c0caaf] uppercase">
                                        Subir imagen de la empresa
                                    </span>
                                )}
                            </button>
                            <input
                                ref={logoInputRef}
                                id="logo"
                                type="file"
                                accept="image/*"
                                onChange={handleLogoChange}
                                className="hidden"
                            />
                            {errors.logo && (
                                <p className={errorClass}>{errors.logo}</p>
                            )}
                        </div>

                        <div className="sm:col-span-2">
                            <label htmlFor="name" className={labelClass}>
                                Nombre de la empresa
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                className={inputClass(Boolean(errors.name))}
                                placeholder="Mantis Industrial S.L."
                            />
                            {errors.name && (
                                <p className={errorClass}>{errors.name}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="tax_id" className={labelClass}>
                                NIF / CIF
                            </label>
                            <input
                                id="tax_id"
                                type="text"
                                value={data.tax_id}
                                onChange={(event) =>
                                    setData('tax_id', event.target.value)
                                }
                                className={inputClass(Boolean(errors.tax_id))}
                                placeholder="B-12345678"
                            />
                            {errors.tax_id && (
                                <p className={errorClass}>{errors.tax_id}</p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="contact_email"
                                className={labelClass}
                            >
                                Email de contacto
                            </label>
                            <input
                                id="contact_email"
                                type="email"
                                value={data.contact_email}
                                onChange={(event) =>
                                    setData('contact_email', event.target.value)
                                }
                                className={inputClass(
                                    Boolean(errors.contact_email),
                                )}
                                placeholder="contacto@empresa.com"
                            />
                            {errors.contact_email && (
                                <p className={errorClass}>
                                    {errors.contact_email}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="phone" className={labelClass}>
                                Teléfono
                            </label>
                            <input
                                id="phone"
                                type="tel"
                                value={data.phone}
                                onChange={(event) =>
                                    setData('phone', event.target.value)
                                }
                                className={inputClass(Boolean(errors.phone))}
                                placeholder="+34 600 000 000"
                            />
                            {errors.phone && (
                                <p className={errorClass}>{errors.phone}</p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="contact_name"
                                className={labelClass}
                            >
                                Persona de contacto
                            </label>
                            <input
                                id="contact_name"
                                type="text"
                                value={data.contact_name}
                                onChange={(event) =>
                                    setData('contact_name', event.target.value)
                                }
                                className={inputClass(
                                    Boolean(errors.contact_name),
                                )}
                                placeholder="Nombre y apellidos"
                            />
                            {errors.contact_name && (
                                <p className={errorClass}>
                                    {errors.contact_name}
                                </p>
                            )}
                        </div>

                        <div className="sm:col-span-2">
                            <label htmlFor="address" className={labelClass}>
                                Dirección
                            </label>
                            <input
                                id="address"
                                type="text"
                                value={data.address}
                                onChange={(event) =>
                                    setData('address', event.target.value)
                                }
                                className={inputClass(Boolean(errors.address))}
                                placeholder="Calle, número, piso"
                            />
                            {errors.address && (
                                <p className={errorClass}>{errors.address}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="city" className={labelClass}>
                                Ciudad
                            </label>
                            <input
                                id="city"
                                type="text"
                                value={data.city}
                                onChange={(event) =>
                                    setData('city', event.target.value)
                                }
                                className={inputClass(Boolean(errors.city))}
                                placeholder="Madrid"
                            />
                            {errors.city && (
                                <p className={errorClass}>{errors.city}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="province" className={labelClass}>
                                Provincia
                            </label>
                            <input
                                id="province"
                                type="text"
                                value={data.province}
                                onChange={(event) =>
                                    setData('province', event.target.value)
                                }
                                className={inputClass(Boolean(errors.province))}
                                placeholder="Madrid"
                            />
                            {errors.province && (
                                <p className={errorClass}>{errors.province}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="postal_code" className={labelClass}>
                                Código postal
                            </label>
                            <input
                                id="postal_code"
                                type="text"
                                value={data.postal_code}
                                onChange={(event) =>
                                    setData('postal_code', event.target.value)
                                }
                                className={inputClass(
                                    Boolean(errors.postal_code),
                                )}
                                placeholder="28001"
                            />
                            {errors.postal_code && (
                                <p className={errorClass}>
                                    {errors.postal_code}
                                </p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="country" className={labelClass}>
                                País
                            </label>
                            <input
                                id="country"
                                type="text"
                                value={data.country}
                                onChange={(event) =>
                                    setData('country', event.target.value)
                                }
                                className={inputClass(Boolean(errors.country))}
                                placeholder="España"
                            />
                            {errors.country && (
                                <p className={errorClass}>{errors.country}</p>
                            )}
                        </div>

                        <div className="flex flex-col gap-4 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between sm:pt-4">
                            <Link
                                href="/access"
                                className="order-2 flex items-center justify-center gap-2 py-2 font-mono text-[14px] tracking-[0.05em] text-[#9cfb2b] uppercase transition-colors hover:underline sm:order-1"
                            >
                                <span aria-hidden="true">←</span>
                                Volver
                            </Link>

                            <button
                                type="submit"
                                disabled={processing}
                                className="order-1 flex w-full items-center justify-center gap-2 bg-[#9bfa2a] px-8 py-4 font-mono text-[14px] font-semibold tracking-[0.05em] text-[#191919] uppercase transition-colors hover:bg-[#83dd00] disabled:cursor-not-allowed disabled:opacity-60 sm:order-2 sm:w-auto"
                            >
                                {processing
                                    ? 'Enviando...'
                                    : 'Solicitar registro'}
                            </button>
                        </div>
                    </form>
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
