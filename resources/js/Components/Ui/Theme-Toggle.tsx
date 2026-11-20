import { MoonIcon, SunIcon } from 'lucide-react';
import { useTheme } from 'next-themes';
import { useEffect, useState } from 'react';

import { Button } from '@/Components/Ui/Button';

/**
 * Alterna entre tema claro y oscuro.
 * Se monta después del primer render para evitar desajustes de hidratación.
 */
export function ThemeToggle() {
    const { resolvedTheme, setTheme } = useTheme();
    const [isMounted, setIsMounted] = useState(false);

    useEffect(() => {
        setIsMounted(true);
    }, []);

    const isDark = isMounted && resolvedTheme === 'dark';
    const label = isDark ? 'Activar modo claro' : 'Activar modo oscuro';

    return (
        <Button
            variant="ghost"
            size="icon-sm"
            aria-label={label}
            title={label}
            onClick={() => setTheme(isDark ? 'light' : 'dark')}
        >
            {isDark ? <SunIcon /> : <MoonIcon />}
        </Button>
    );
}
