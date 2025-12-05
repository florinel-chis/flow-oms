<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FlowOMS - Order Management System</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 min-h-screen">
    <div class="relative min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">

        {{-- Header/Logo --}}
        <div class="text-center mb-12">
            <div class="flex justify-center items-center mb-6">
                <img src="{{ asset('images/flowoms-logo.webp') }}"
                     alt="FlowOMS Logo"
                     class="h-20 w-auto">
                <h1 class="text-5xl font-bold text-gray-900 dark:text-white ml-4">
                    FlowOMS
                </h1>
            </div>
            <p class="text-xl text-gray-600 dark:text-gray-300">
                Multi-Tenant Order Management System for Magento 2
            </p>
        </div>

        {{-- Hero Section --}}
        <div class="max-w-4xl mx-auto text-center mb-16">
            <p class="text-lg text-gray-700 dark:text-gray-300 mb-8">
                Streamline your e-commerce operations with powerful order management,
                real-time Magento 2 synchronization, and comprehensive operational dashboards.
            </p>

            <a href="/admin"
               class="inline-flex items-center px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Access Admin Panel
            </a>
        </div>

        {{-- Request a Demo Section --}}
        <div class="max-w-3xl mx-auto mb-16">
            <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-center transform hover:scale-[1.02] transition-transform duration-200">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-full mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-white mb-3">
                    Request a Demo
                </h2>
                <p class="text-xl text-white mb-6">
                    Interested in FlowOMS? Get in touch with us to schedule a personalized demo.
                </p>
                <a href="mailto:info@magendoo.ro"
                   class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-bold rounded-lg shadow-lg hover:shadow-xl hover:bg-gray-50 transition-all duration-200 transform hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    info@magendoo.ro
                </a>
                <p class="text-sm text-white opacity-90 mt-4">
                    We'll respond within 24 hours
                </p>
            </div>
        </div>

        {{-- How It Works Section --}}
        <div class="max-w-5xl mx-auto mb-20">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                How It Works
            </h2>
            <div class="relative">
                {{-- Process Flow --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    {{-- Step 1 --}}
                    <div class="relative">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200">
                            <div class="flex items-center justify-center w-12 h-12 bg-blue-600 text-white rounded-full font-bold text-xl mb-4 mx-auto">
                                1
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 text-center">Connect</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                                Configure your Magento 2 store credentials and API endpoints
                            </p>
                        </div>
                        {{-- Arrow (hidden on mobile) --}}
                        <div class="hidden md:block absolute top-1/2 -right-3 transform -translate-y-1/2 z-10">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="relative">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200">
                            <div class="flex items-center justify-center w-12 h-12 bg-purple-600 text-white rounded-full font-bold text-xl mb-4 mx-auto">
                                2
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 text-center">Sync</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                                Automatic order, invoice, and shipment synchronization from Magento
                            </p>
                        </div>
                        {{-- Arrow (hidden on mobile) --}}
                        <div class="hidden md:block absolute top-1/2 -right-3 transform -translate-y-1/2 z-10">
                            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>

                    {{-- Step 3 --}}
                    <div class="relative">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200">
                            <div class="flex items-center justify-center w-12 h-12 bg-green-600 text-white rounded-full font-bold text-xl mb-4 mx-auto">
                                3
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 text-center">Monitor</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                                Track KPIs, orders, shipments, and SLA compliance in real-time
                            </p>
                        </div>
                        {{-- Arrow (hidden on mobile) --}}
                        <div class="hidden md:block absolute top-1/2 -right-3 transform -translate-y-1/2 z-10">
                            <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>

                    {{-- Step 4 --}}
                    <div class="relative">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow duration-200">
                            <div class="flex items-center justify-center w-12 h-12 bg-orange-600 text-white rounded-full font-bold text-xl mb-4 mx-auto">
                                4
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 text-center">Extend</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                                Integrate with external systems via REST API and webhooks
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dashboard Features Section --}}
        <div class="max-w-6xl mx-auto mb-20">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4 text-center">
                Operational Dashboard
            </h2>
            <p class="text-center text-gray-600 dark:text-gray-400 mb-12 max-w-3xl mx-auto">
                Get real-time insights into your order fulfillment operations with comprehensive widgets and actionable metrics
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                {{-- KPI Stats Widget --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-t-4 border-blue-500">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">KPI Stats Overview</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Eight real-time KPI cards showing critical metrics:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <strong>Orders:</strong> Total count placed in date range
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <strong>Revenue:</strong> Total captured and invoiced
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <strong>AOV:</strong> Average order value (revenue ÷ orders)
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <strong>Shipped:</strong> Completed orders + active shipments in transit
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <strong>Unpaid:</strong> Orders with pending payment + amount
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <strong>Ready to Ship:</strong> Orders ready + urgent count
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <strong>Exceptions:</strong> Backorders + delayed shipments
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            <strong>SLA:</strong> Compliance percentage with target
                        </li>
                    </ul>
                </div>

                {{-- Unpaid Orders Widget --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-t-4 border-red-500">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Unpaid Orders</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Track orders with pending payments:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Orders awaiting payment confirmation
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Bulk action: Send payment reminders
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Sortable by days outstanding
                        </li>
                    </ul>
                </div>

                {{-- Ready to Ship Widget --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-t-4 border-green-500">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Ready to Ship</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Manage orders ready for fulfillment:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Priority-based sorting (urgent, high, normal)
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Bulk action: Assign picker to orders
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Quick access to picking lists
                        </li>
                    </ul>
                </div>

                {{-- Delayed Shipments Widget --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-t-4 border-yellow-500">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Delayed Shipments</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Monitor shipments with potential delays:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Carrier tracking integration
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Days since shipment highlighting
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Bulk action: Contact carrier
                        </li>
                    </ul>
                </div>

                {{-- Backordered Items Widget --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-t-4 border-purple-500">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Backordered Items</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Track out-of-stock and backordered items:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Group by product with order counts
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Total quantity backordered per SKU
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Quick access to affected orders
                        </li>
                    </ul>
                </div>

                {{-- SLA Shipping Monitor --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border-t-4 border-indigo-500">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">SLA Shipping Monitor</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Track shipping deadlines and SLA compliance:
                    </p>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Automatic deadline calculation
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Visual alerts for at-risk orders
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Configurable SLA thresholds per tenant
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Core Features Section --}}
        <div class="max-w-6xl mx-auto mb-20">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">
                Core Features
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                {{-- Feature 1: Multi-Tenant --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-shadow duration-200">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Multi-Tenant Architecture</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Manage multiple companies and stores from a single platform with complete data isolation and tenant-specific configurations.
                    </p>
                </div>

                {{-- Feature 2: Magento Integration --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-shadow duration-200">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Magento 2 Integration</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Seamless bi-directional sync with Magento 2 stores. Import orders, products, invoices, and shipments with full support for bundle and configurable products.
                    </p>
                </div>

                {{-- Feature 3: Order Management --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-shadow duration-200">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Advanced Order Management</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Complete order lifecycle management with status tracking, bulk actions, filtering, and automated workflows for efficient processing.
                    </p>
                </div>

                {{-- Feature 4: Shipping Integration --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-shadow duration-200">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Shipping & Tracking</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Integrated with major carriers (UPS, DHL, FedEx, AfterShip) for real-time tracking and automated shipping status updates via webhooks.
                    </p>
                </div>

                {{-- Feature 5: Product Hierarchy --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-shadow duration-200">
                    <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Product Hierarchy Support</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Full support for Magento bundle and configurable products with parent-child relationships preserved and visualized in order items.
                    </p>
                </div>

                {{-- Feature 6: Notification System --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-xl transition-shadow duration-200">
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Smart Notifications</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Automated email notifications for unpaid orders, SLA breaches, and delayed shipments with configurable templates and scheduling.
                    </p>
                </div>

            </div>
        </div>

        {{-- API & Webhooks Section --}}
        <div class="max-w-6xl mx-auto mb-20">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4 text-center">
                Extend with REST API & Webhooks
            </h2>
            <p class="text-center text-gray-600 dark:text-gray-400 mb-12 max-w-3xl mx-auto">
                FlowOMS provides a comprehensive REST API and webhook system for seamless integration with your existing tools and workflows
            </p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                {{-- REST API --}}
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-xl shadow-lg p-8 border-2 border-blue-200 dark:border-blue-800">
                    <div class="flex items-center mb-6">
                        <div class="w-14 h-14 bg-blue-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">REST API</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Read & write data programmatically</p>
                        </div>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">📦 Orders API</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Retrieve and manage orders</p>
                            <code class="text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded block overflow-x-auto">
                                GET /api/v1/orders<br>
                                GET /api/v1/orders/{increment_id}
                            </code>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">📄 Invoices API</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Access invoice data and items</p>
                            <code class="text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded block overflow-x-auto">
                                GET /api/v1/invoices<br>
                                GET /api/v1/invoices/{increment_id}
                            </code>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🚚 Shipments API</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Update delivery status</p>
                            <code class="text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded block overflow-x-auto">
                                POST /api/v1/shipments/{id}/delivery
                            </code>
                        </div>
                    </div>

                    <div class="border-t border-blue-200 dark:border-blue-800 pt-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Features:</h4>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300"><strong>Sanctum authentication</strong> with tenant-scoped tokens</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300"><strong>Advanced filtering:</strong> status, payment, date ranges</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300"><strong>Pagination & relationships</strong> (items, shipments)</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300"><strong>Rate limiting:</strong> 60 requests/minute per token</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300"><strong>Comprehensive logging</strong> in database table</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Webhooks --}}
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-gray-800 dark:to-gray-700 rounded-xl shadow-lg p-8 border-2 border-purple-200 dark:border-purple-800">
                    <div class="flex items-center mb-6">
                        <div class="w-14 h-14 bg-purple-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Webhooks</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Receive real-time event notifications</p>
                        </div>
                    </div>

                    <div class="space-y-4 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">📫 Shipment Status Updates</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Receive tracking updates from carriers</p>
                            <code class="text-xs bg-gray-100 dark:bg-gray-900 p-2 rounded block overflow-x-auto">
                                POST /api/v1/webhooks/shipment-status
                            </code>
                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                <strong>Supports:</strong> in_transit, out_for_delivery, delivered, exception
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">🔔 Payment Notifications</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Payment gateway event integration</p>
                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                <strong>Coming soon:</strong> Stripe, PayPal, Authorize.net webhooks
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">📦 Inventory Updates</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Stock level change notifications</p>
                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                <strong>Coming soon:</strong> Real-time inventory sync from warehouses
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-purple-200 dark:border-purple-800 pt-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Features:</h4>
                        <ul class="space-y-2 text-sm">
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-purple-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300"><strong>Signature verification</strong> for security</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-purple-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300"><strong>Automatic retries</strong> with exponential backoff</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-purple-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300"><strong>Delivery history</strong> and status tracking</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-purple-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300"><strong>Event filtering</strong> by type and tenant</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Integration Use Cases --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">Integration Use Cases</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Custom Reporting</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Pull order data into your BI tools (Tableau, Power BI) for advanced analytics and custom dashboards
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Slack/Teams Alerts</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Get instant notifications in Slack or Microsoft Teams when orders require attention or SLAs are at risk
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Warehouse Integration</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Connect to WMS systems (ShipStation, ShipBob) to sync order fulfillment and tracking data automatically
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Benefits Section --}}
        <div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                Why Choose FlowOMS?
            </h2>
            <div class="space-y-4">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-green-500 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-gray-700 dark:text-gray-300">
                        <span class="font-semibold">Reduce Manual Work:</span> Automate order synchronization, status updates, and notifications to save hours of manual data entry.
                    </p>
                </div>
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-green-500 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-gray-700 dark:text-gray-300">
                        <span class="font-semibold">Improve Accuracy:</span> Eliminate data discrepancies between Magento and your operations with real-time synchronization.
                    </p>
                </div>
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-green-500 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-gray-700 dark:text-gray-300">
                        <span class="font-semibold">Scale Effortlessly:</span> Manage multiple Magento stores and tenants from a single unified platform built on Laravel 12.
                    </p>
                </div>
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-green-500 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-gray-700 dark:text-gray-300">
                        <span class="font-semibold">Gain Visibility:</span> Track order status, shipping delays, and SLA compliance with comprehensive dashboards and real-time widgets.
                    </p>
                </div>
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-green-500 mr-3 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-gray-700 dark:text-gray-300">
                        <span class="font-semibold">Extend Easily:</span> Connect to external systems via REST API and webhooks for seamless integration with your existing tools.
                    </p>
                </div>
            </div>
        </div>

        {{-- CTA Section --}}
        <div class="text-center">
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Ready to streamline your e-commerce operations?
            </p>
            <a href="/admin"
               class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                Get Started
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </div>

        {{-- Footer --}}
        <div class="mt-16 text-center text-sm text-gray-500 dark:text-gray-400">
            <p>Built with Laravel 12 and Filament 4</p>
            <p class="mt-2">FlowOMS &copy; {{ date('Y') }}</p>
        </div>

    </div>
</body>
</html>
