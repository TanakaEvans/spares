import AppLayout from '../Layouts/AppLayout'

export default function Welcome() {
    return (
        <AppLayout title="Welcome to Canteen Manager">
            <div className="bg-white overflow-hidden shadow rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <div className="text-center">
                        <h1 className="text-3xl font-bold text-gray-900 mb-4">
                            Welcome to Canteen Manager
                        </h1>
                        <p className="text-lg text-gray-600 mb-8">
                            Your comprehensive canteen management system
                        </p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                        {/* Menu Management Card */}
                        <div className="bg-orange-50 border border-orange-200 rounded-lg p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <svg className="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-lg font-medium text-orange-900">Menu Management</h3>
                                    <p className="text-sm text-orange-700">Manage food items, categories, and pricing</p>
                                </div>
                            </div>
                        </div>

                        {/* Order Management Card */}
                        <div className="bg-green-50 border border-green-200 rounded-lg p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <svg className="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-lg font-medium text-green-900">Order Management</h3>
                                    <p className="text-sm text-green-700">Process orders and track sales</p>
                                </div>
                            </div>
                        </div>

                        {/* Reports Card */}
                        <div className="bg-purple-50 border border-purple-200 rounded-lg p-6">
                            <div className="flex items-center">
                                <div className="flex-shrink-0">
                                    <svg className="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div className="ml-3">
                                    <h3 className="text-lg font-medium text-purple-900">Reports & Analytics</h3>
                                    <p className="text-sm text-purple-700">Generate sales reports and analytics</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8 text-center">
                        <button className="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded">
                            Get Started
                        </button>
                    </div>
                </div>
            </div>
        </AppLayout>
    )
}
