<x-app-layout>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center max-w-md">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Cancelled</h1>
            <p class="text-gray-500 mb-6">No charge was made. You can try again any time.</p>
            <a href="{{ route('employer.dashboard') }}"
               class="inline-block bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2.5 px-6 rounded-xl transition-colors">
                Back to Dashboard
            </a>
        </div>
    </div>
</x-app-layout>
