import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Wrench, Mail, Lock, Eye, EyeOff, ArrowRight, AlertTriangle, Boxes, ShoppingCart, ShieldCheck } from 'lucide-react';

const FEATURES = [
    { icon: Boxes, title: 'Parts & stock', text: 'Multi-branch inventory, AVCO costing, live reorder.' },
    { icon: ShoppingCart, title: 'Sell & procure', text: 'POS, quotes, purchasing and 3-way matched invoices.' },
    { icon: ShieldCheck, title: 'Books that reconcile', text: 'Double-entry ledger, workshop and reports in one place.' },
];

export default function Login({ status }) {
    const { flash = {}, company } = usePage().props;
    const brand = (company?.trading_name || company?.name || 'SparesPro').toUpperCase();
    const logo = company?.logo ? `/${company.logo.replace(/^\/+/, '')}` : null;
    const { data, setData, post, processing, errors, reset } = useForm({
        login: '',
        password: '',
        remember: false,
    });
    const [showPassword, setShowPassword] = useState(false);

    useEffect(() => () => reset('password'), []);

    const submit = (e) => {
        e.preventDefault();
        post('/login');
    };

    const field = 'w-full rounded-lg border border-slate-300 bg-white py-3 pl-11 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30 focus:outline-none transition';

    return (
        <div className="min-h-screen flex bg-white">
            <Head title="Log in" />

            {/* Hero */}
            <div className="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-slate-950 text-white">
                <div className="absolute inset-0" style={{ background: 'radial-gradient(120% 120% at 15% 10%, rgba(234,88,12,0.35) 0%, rgba(2,6,23,0) 45%), radial-gradient(100% 100% at 90% 90%, rgba(30,58,138,0.35) 0%, rgba(2,6,23,0) 50%)' }} />
                <div className="absolute inset-0 opacity-[0.06]" style={{ backgroundImage: 'linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px)', backgroundSize: '38px 38px' }} />

                <div className="relative z-10 flex h-full w-full flex-col justify-between p-12">
                    <div className="flex items-center gap-3">
                        {logo ? (
                            <img src={logo} alt={brand} className="h-14 w-14 rounded-xl object-cover ring-1 ring-white/15 shadow-lg shadow-black/40" />
                        ) : (
                            <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-600 shadow-lg shadow-orange-900/40">
                                <Wrench className="h-6 w-6 text-white" />
                            </div>
                        )}
                        <span className="text-xl font-bold tracking-widest">{brand}</span>
                    </div>

                    <div className="max-w-md">
                        <h1 className="text-4xl font-bold leading-tight">The complete motor-spares ERP</h1>
                        <p className="mt-4 text-base leading-relaxed text-slate-300">
                            Parts, stock, sales, workshop and accounts — everything your spares business runs on, in one system.
                        </p>
                        <div className="mt-10 space-y-5">
                            {FEATURES.map((f) => (
                                <div key={f.title} className="flex items-start gap-3.5">
                                    <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15">
                                        <f.icon className="h-5 w-5 text-orange-400" />
                                    </div>
                                    <div>
                                        <div className="text-sm font-semibold text-white">{f.title}</div>
                                        <div className="text-sm text-slate-400">{f.text}</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="flex gap-3 text-xs text-slate-400">
                        <span>© {new Date().getFullYear()} {company?.trading_name || company?.name || 'SparesPro'}</span>
                        <span>•</span><span>Bochum, Limpopo</span>
                        <span>•</span><span>Powered by SparesPro</span>
                    </div>
                </div>
            </div>

            {/* Form */}
            <div className="flex flex-1 flex-col justify-center px-6 py-12 sm:px-10 lg:px-20 xl:px-28">
                <div className="mx-auto w-full max-w-sm">
                    <div className="mb-8 flex items-center gap-3 lg:hidden">
                        {logo ? (
                            <img src={logo} alt={brand} className="h-12 w-12 rounded-xl object-cover ring-1 ring-slate-200" />
                        ) : (
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-600">
                                <Wrench className="h-5 w-5 text-white" />
                            </div>
                        )}
                        <span className="text-lg font-bold tracking-widest text-slate-900">{brand}</span>
                    </div>

                    <h2 className="text-2xl font-bold tracking-tight text-slate-900">Welcome back</h2>
                    <p className="mt-1.5 text-sm text-slate-500">Sign in to continue to your workspace.</p>

                    {status && (
                        <div className="mt-6 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{status}</div>
                    )}
                    {flash.error && (
                        <div className="mt-6 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <AlertTriangle className="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{flash.error}</span>
                        </div>
                    )}

                    <form onSubmit={submit} className="mt-7 space-y-5">
                        <div>
                            <label htmlFor="login" className="mb-1.5 block text-sm font-medium text-slate-700">Username or email</label>
                            <div className="relative">
                                <Mail className="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                <input
                                    id="login" name="login" type="text" autoFocus autoComplete="username"
                                    value={data.login}
                                    onChange={(e) => setData('login', e.target.value)}
                                    placeholder="admin or user@company.com"
                                    className={field}
                                />
                            </div>
                            {errors.login && <p className="mt-1.5 text-sm text-red-600">{errors.login}</p>}
                        </div>

                        <div>
                            <label htmlFor="password" className="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                            <div className="relative">
                                <Lock className="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
                                <input
                                    id="password" name="password" type={showPassword ? 'text' : 'password'} autoComplete="current-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••"
                                    className={`${field} pr-11`}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword((v) => !v)}
                                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                >
                                    {showPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                                </button>
                            </div>
                            {errors.password && <p className="mt-1.5 text-sm text-red-600">{errors.password}</p>}
                        </div>

                        <label className="flex items-center gap-2 text-sm text-slate-600">
                            <input
                                type="checkbox"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                            />
                            Remember me
                        </label>

                        <button
                            type="submit"
                            disabled={processing}
                            className="flex w-full items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {processing ? 'Signing in…' : <>Sign in <ArrowRight className="h-4 w-4" /></>}
                        </button>
                    </form>

                    <p className="mt-8 text-center text-sm text-slate-500">
                        Trouble signing in? Contact your system administrator.
                    </p>
                </div>
            </div>
        </div>
    );
}
