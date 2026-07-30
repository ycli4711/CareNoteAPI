import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="登录" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-5"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5">
                            <div className="grid gap-2.5">
                                <Label
                                    htmlFor="email"
                                    className="text-sm font-medium text-[#264b41] dark:text-emerald-50"
                                >
                                    邮箱地址
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                    className="h-12 rounded-xl border-[#d7e6df] bg-white/80 px-4 shadow-none transition-[border-color,box-shadow] placeholder:text-[#9aacA5] focus-visible:border-[#2b8068] focus-visible:ring-[#2b8068]/15 dark:border-white/10 dark:bg-black/10"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2.5">
                                <div className="flex items-center">
                                    <Label
                                        htmlFor="password"
                                        className="text-sm font-medium text-[#264b41] dark:text-emerald-50"
                                    >
                                        密码
                                    </Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm text-[#26715d] underline-offset-4 hover:text-[#164c3e] dark:text-[#83cdb4]"
                                            tabIndex={5}
                                        >
                                            忘记密码？
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="请输入密码"
                                    className="h-12 rounded-xl border-[#d7e6df] bg-white/80 px-4 shadow-none transition-[border-color,box-shadow] placeholder:text-[#9aaca5] focus-visible:border-[#2b8068] focus-visible:ring-[#2b8068]/15 dark:border-white/10 dark:bg-black/10"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3 py-1">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                    className="border-[#a9c8bc] data-[state=checked]:border-[#17624e] data-[state=checked]:bg-[#17624e]"
                                />
                                <Label
                                    htmlFor="remember"
                                    className="cursor-pointer text-sm font-normal text-[#5c746c] dark:text-[#a2b9b0]"
                                >
                                    记住我
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-1 h-12 w-full rounded-xl bg-[#145b48] text-[15px] font-semibold tracking-[0.16em] text-white shadow-none transition-colors hover:bg-[#0e4b3b] dark:bg-[#79c9ad] dark:text-[#07241c] dark:hover:bg-[#94d7c0]"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                登录
                            </Button>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: '登录您的账户',
    description: '请输入邮箱地址和密码',
};
