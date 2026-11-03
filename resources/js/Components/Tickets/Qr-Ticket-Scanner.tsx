import { router } from '@inertiajs/react';
import { LoaderCircle, QrCode, ScanLine } from 'lucide-react';
import QrScanner from 'qr-scanner';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/Components/Ui/Button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/Components/Ui/Dialog';
import tickets from '@/routes/tickets';

const SCAN_ERROR_MESSAGE =
    'No se pudo acceder a la cámara. Revisa los permisos del navegador e intenta de nuevo.';

const INVALID_QR_MESSAGE =
    'El código QR escaneado no corresponde a un activo. Apunta al QR de la ficha del activo.';

function assetCodeFromQr(rawValue: string): string | null {
    const value = rawValue.trim();

    if (value === '') {
        return null;
    }

    if (/^https?:\/\//i.test(value) || value.startsWith('/')) {
        try {
            const url = new URL(value, window.location.origin);
            const code = url.searchParams.get('asset')?.trim();

            return code ? code : null;
        } catch {
            return null;
        }
    }

    return value;
}

export function QrTicketScanner() {
    const [open, setOpen] = useState(false);
    const [starting, setStarting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [video, setVideo] = useState<HTMLVideoElement | null>(null);

    const invalidValueRef = useRef<string | null>(null);
    const handledRef = useRef(false);

    useEffect(() => {
        if (!open || video === null) {
            return;
        }

        handledRef.current = false;
        invalidValueRef.current = null;

        let cancelled = false;

        const scanner = new QrScanner(
            video,
            (result) => {
                if (handledRef.current) {
                    return;
                }

                const code = assetCodeFromQr(result.data);

                if (code === null) {
                    if (invalidValueRef.current !== result.data) {
                        invalidValueRef.current = result.data;
                        setError(INVALID_QR_MESSAGE);
                    }

                    return;
                }

                handledRef.current = true;
                setOpen(false);
                router.visit(tickets.create.url({ query: { asset: code } }));
            },
            {
                preferredCamera: 'environment',
                highlightScanRegion: true,
                maxScansPerSecond: 10,
                returnDetailedScanResult: true,
            },
        );

        scanner
            .start()
            .then(() => {
                if (!cancelled) {
                    setStarting(false);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setStarting(false);
                    setError(SCAN_ERROR_MESSAGE);
                }
            });

        return () => {
            cancelled = true;
            scanner.destroy();
        };
    }, [open, video]);

    return (
        <>
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="hidden max-xl:inline-flex pointer-coarse:inline-flex"
                onClick={() => {
                    setError(null);
                    setStarting(true);
                    setOpen(true);
                }}
            >
                <ScanLine />
                Escanear QR
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <QrCode className="size-4" />
                            Escanear código QR
                        </DialogTitle>

                        <DialogDescription>
                            Apunta la cámara al código QR de la ficha del activo
                            para reportar una incidencia.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="relative overflow-hidden rounded-lg bg-black">
                        <video
                            ref={setVideo}
                            className="aspect-square w-full object-cover"
                            muted
                            playsInline
                        />

                        {starting && (
                            <div className="absolute inset-0 flex items-center justify-center bg-black/60 text-white">
                                <LoaderCircle className="size-8 animate-spin" />
                            </div>
                        )}
                    </div>

                    {error && (
                        <p
                            className="mt-3 text-sm text-destructive"
                            role="alert"
                        >
                            {error}
                        </p>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
