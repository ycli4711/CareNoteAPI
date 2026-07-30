import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <main className="flex min-h-svh items-center justify-center bg-[#f6f8f7] px-5 py-10 text-[#173d33] dark:bg-[#0b1512] dark:text-[#e8f4ef]">
            <div className="w-full max-w-[440px]">
                <Link
                    href={home()}
                    className="mx-auto mb-7 flex w-fit items-center gap-3"
                >
                    <span className="flex size-14 items-center justify-center overflow-hidden rounded-xl border border-[#dce8e2] bg-white dark:border-white/10">
                        <AppLogoIcon className="size-full object-cover" />
                    </span>
                    <span className="text-left">
                        <span className="font-brand block text-xl font-semibold tracking-wide">
                            家庭药箱
                        </span>
                        <span className="mt-0.5 block text-[10px] tracking-[0.2em] text-[#6f877e] dark:text-[#8ca69c]">
                            CARENOTE 管理后台
                        </span>
                    </span>
                </Link>

                <section className="rounded-2xl border border-[#e0e9e5] bg-white p-7 shadow-[0_8px_24px_rgba(26,68,56,0.06)] sm:p-9 dark:border-white/10 dark:bg-[#111f1a] dark:shadow-black/20">
                    <header className="mb-8">
                        <h1 className="font-brand text-3xl leading-tight font-semibold tracking-tight text-[#123b31] dark:text-[#ecf8f3]">
                            {title}
                        </h1>
                        <p className="mt-2 text-sm leading-6 text-[#687e76] dark:text-[#96aaa2]">
                            {description}
                        </p>
                    </header>

                    {children}
                </section>

                <p className="mt-6 text-center text-xs text-[#8a9c95] dark:text-[#71877e]">
                    安全登录 · 仅限授权管理员访问
                </p>
            </div>
        </main>
    );
}
