import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';

export default function LoginTest({ status, errors }) {
    const { data, setData, post, processing, reset } = useForm({
        login: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Login Test" />
            <div className="min-h-screen bg-blue-500 flex items-center justify-center">
                <div className="bg-white p-8 rounded-lg shadow-lg">
                    <h1 className="text-2xl font-bold mb-4">Login Test</h1>
                    <form onSubmit={submit}>
                        <div className="mb-4">
                            <input
                                type="text"
                                placeholder="Email"
                                value={data.login}
                                onChange={(e) => setData('login', e.target.value)}
                                className="w-full p-2 border border-gray-300 rounded"
                            />
                        </div>
                        <div className="mb-4">
                            <input
                                type="password"
                                placeholder="Password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="w-full p-2 border border-gray-300 rounded"
                            />
                        </div>
                        <button 
                            type="submit"
                            disabled={processing}
                            className="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700"
                        >
                            {processing ? 'Loading...' : 'Sign In'}
                        </button>
                    </form>
                </div>
            </div>
        </>
    );
}