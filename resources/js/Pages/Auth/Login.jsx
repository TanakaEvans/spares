import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Button from '@/Components/Button';
import TextInput from '@/Components/TextInput';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();
        console.log('Form submitting with data:', data);
        post('/login', {
            onSuccess: () => {
                console.log('Login successful');
            },
            onError: (errors) => {
                console.log('Login errors:', errors);
            }
        });
    };

    return (
        <div className="min-h-screen flex bg-gray-50">
            <Head title="Log in" />

            {/* Left Side - Hero Image */}
            <div className="hidden lg:flex lg:w-1/2 relative overflow-hidden bg-blue-900">
                <div
                    className="absolute inset-0 bg-cover bg-center"
                    style={{ backgroundImage: 'url(/images/school-login-bg.png)' }}
                ></div>
                <div className="absolute inset-0 bg-blue-900/40"></div>
                <div className="relative z-10 w-full h-full flex flex-col justify-between p-12 text-white">
                    <div>
                        <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-white/20 backdrop-blur rounded-lg flex items-center justify-center">
                                <span className="text-2xl">🎓</span>
                            </div>
                            <span className="text-xl font-bold tracking-wider">EDU-MANAGER</span>
                        </div>
                    </div>
                    <div className="max-w-md">
                        <h1 className="text-4xl font-bold mb-6 leading-tight">Welcome to the Future of School Management</h1>
                        <p className="text-lg text-blue-100/90 leading-relaxed">
                            Streamline administration, enhance learning, and manage your institution with efficiency and ease.
                        </p>
                    </div>
                    <div className="flex gap-4 text-sm text-blue-200/80">
                        <span>© 2025 Education Systems</span>
                        <span>•</span>
                        <span>Privacy Policy</span>
                        <span>•</span>
                        <span>Terms</span>
                    </div>
                </div>
            </div>

            {/* Right Side - Login Form */}
            <div className="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24 bg-white">
                <div className="mx-auto w-full max-w-sm lg:w-96">
                    <div className="mb-10">
                        <h2 className="text-3xl font-extrabold text-blue-900 tracking-tight">
                            Sign In
                        </h2>
                        <p className="mt-2 text-sm text-gray-500">
                            Please authenticate with your credentials.
                        </p>
                    </div>

                    {status && <div className="mb-4 bg-green-50 text-green-700 p-4 rounded-lg text-sm font-medium border border-green-100">{status}</div>}

                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="login" value="Username or Email" className="block text-sm font-medium text-gray-700" />
                            <div className="mt-1 relative rounded-md shadow-sm">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span className="text-gray-400 sm:text-sm">📧</span>
                                </div>
                                <TextInput
                                    id="login"
                                    type="text"
                                    name="login"
                                    value={data.login}
                                    className="block w-full pl-10 pr-3 py-3 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    isFocused={true}
                                    onChange={(e) => setData('login', e.target.value)}
                                    placeholder="admin or user@school.com"
                                />
                            </div>
                            <InputError message={errors.login} className="mt-2 text-sm text-red-600" />
                        </div>

                        <div>
                            <InputLabel htmlFor="password" value="Password" className="block text-sm font-medium text-gray-700" />
                            <div className="mt-1 relative rounded-md shadow-sm">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span className="text-gray-400 sm:text-sm">🔒</span>
                                </div>
                                <TextInput
                                    id="password"
                                    type={showPassword ? "text" : "password"}
                                    name="password"
                                    value={data.password}
                                    className="block w-full pl-10 pr-10 py-3 border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••"
                                />
                                <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="text-gray-400 hover:text-gray-500 focus:outline-none"
                                    >
                                        {showPassword ? (
                                            <span className="text-xs font-semibold uppercase">Hide</span>
                                        ) : (
                                            <span className="text-xs font-semibold uppercase">Show</span>
                                        )}
                                    </button>
                                </div>
                            </div>
                            <InputError message={errors.password} className="mt-2 text-sm text-red-600" />
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                <Checkbox
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label htmlFor="remember" className="ml-2 block text-sm text-gray-900">
                                    Remember me
                                </label>
                            </div>

                            {canResetPassword && (
                                <div className="text-sm">
                                    <Link
                                        href={route('password.request')}
                                        className="font-medium text-blue-600 hover:text-blue-500"
                                    >
                                        Forgot password?
                                    </Link>
                                </div>
                            )}
                        </div>

                        <div>
                            <Button
                                type="submit"
                                className="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-gradient-to-r from-blue-700 to-indigo-800 hover:from-blue-800 hover:to-indigo-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:-translate-y-0.5"
                                disabled={processing}
                                processing={processing}
                            >
                                {processing ? 'Signing in...' : 'Sign In'}
                            </Button>
                        </div>
                    </form>

                    <div className="mt-8 text-center text-sm text-gray-500">
                        Need help? <a href="#" className="font-medium text-blue-600 hover:text-blue-500">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    );
}
