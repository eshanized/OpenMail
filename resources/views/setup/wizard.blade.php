@extends('layouts.setup')

@section('content')
    @verbatim
    <div x-data="setupWizard()">
        <nav class="mb-8" aria-label="Setup wizard progress">
            <ol class="flex items-center justify-between">
                @for ($i = 1; $i <= 8; $i++)
                    <li class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2
                            @if ($i < 1) bg-blue-600 border-blue-600 text-white
                            @elseif ($i === 1) bg-blue-600 border-blue-600 text-white
                            @else bg-white border-gray-300 text-gray-500
                            @endif
                            text-sm font-medium">
                            {{ $i }}
                        </div>
                        @if ($i < 8)
                            <div class="w-full sm:w-24 h-0.5
                                @if ($i < 1) bg-blue-600 @else bg-gray-200 @endif
                                ml-2 mr-2"></div>
                        @endif
                    </li>
                @endfor
            </ol>
            <div class="mt-4 text-center text-sm text-gray-500">
                <span x-text="stepLabels[currentStep] || 'Step ' + currentStep"></span>
            </div>
        </nav>

        <div class="space-y-6">
            <!-- Step 1: Welcome -->
            <div x-show="currentStep === 1" x-transition>
                <h3 class="text-lg font-medium text-gray-900">Welcome to OpenMail Setup</h3>
                <p class="mt-2 text-sm text-gray-600">This wizard will guide you through configuring OpenMail.</p>
                <div class="mt-6">
                    <label for="app_name" class="block text-sm font-medium text-gray-700">Application Name</label>
                    <input type="text" id="app_name" name="app_name" x-model="appName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" @click="nextStep" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Next
                    </button>
                </div>
            </div>

            <!-- Step 2: System Requirements -->
            <div x-show="currentStep === 2" x-transition>
                <h3 class="text-lg font-medium text-gray-900">System Requirements</h3>
                <p class="mt-2 text-sm text-gray-600">Checking if your server meets the requirements.</p>
                <div class="mt-4 space-y-3" x-data="{ requirements: @json($requirements ?? []) }">
                    <template x-for="check in requirements" :key="check.label">
                        <div class="flex items-center justify-between p-3 border rounded-md">
                            <span x-text="check.label" class="text-sm text-gray-700"></span>
                            <span :class="check.passed ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                                <span x-text="check.passed ? '✓ Passed' : '✗ Failed'"></span>
                            </span>
                        </div>
                    </template>
                </div>
                <div class="mt-4 flex justify-between">
                    <button type="button" @click="previousStep" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Previous
                    </button>
                    <button type="button" @click="nextStep" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Next
                    </button>
                </div>
            </div>

            <!-- Step 3: Database Config -->
            <div x-show="currentStep === 3" x-transition>
                <h3 class="text-lg font-medium text-gray-900">Database Configuration</h3>
                <p class="mt-2 text-sm text-gray-600">Configure your database connection.</p>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="db_host" class="block text-sm font-medium text-gray-700">Host</label>
                        <input type="text" id="db_host" name="db_host" x-model="dbHost" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" value="127.0.0.1">
                    </div>
                    <div>
                        <label for="db_port" class="block text-sm font-medium text-gray-700">Port</label>
                        <input type="number" id="db_port" name="db_port" x-model="dbPort" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" value="3306">
                    </div>
                    <div>
                        <label for="db_database" class="block text-sm font-medium text-gray-700">Database Name</label>
                        <input type="text" id="db_database" name="db_database" x-model="dbDatabase" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                    </div>
                    <div>
                        <label for="db_username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" id="db_username" name="db_username" x-model="dbUsername" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    </div>
                    <div>
                        <label for="db_password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" id="db_password" name="db_password" x-model="dbPassword" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    </div>
                    <div>
                        <button type="button" @click="testDatabaseConnection" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Test Connection
                        </button>
                        <span x-show="dbTestResult" class="ml-3 text-sm" :class="dbTestResult.success ? 'text-green-600' : 'text-red-600'">
                            <span x-text="dbTestResult.success ? '✓ Connected successfully' : '✗ ' + dbTestResult.error"></span>
                        </span>
                    </div>
                </div>
                <div class="mt-4 flex justify-between">
                    <button type="button" @click="previousStep" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Previous
                    </button>
                    <button type="button" @click="nextStep" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Next
                    </button>
                </div>
            </div>

            <!-- Step 4: Mail Config (placeholder) -->
            <div x-show="currentStep === 4" x-transition>
                <h3 class="text-lg font-medium text-gray-900">Mail Configuration (IMAP/SMTP)</h3>
                <p class="mt-2 text-sm text-gray-600">Configure your mail server settings. Will be fully implemented in Plan 2.</p>
                <div class="mt-4 flex justify-between">
                    <button type="button" @click="previousStep" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Previous
                    </button>
                    <button type="button" @click="nextStep" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Next
                    </button>
                </div>
            </div>

            <!-- Steps 5-8 placeholders -->
            <div x-show="currentStep >= 5 && currentStep <= 8" x-transition>
                <h3 class="text-lg font-medium text-gray-900">Step {{ currentStep }}</h3>
                <p class="mt-2 text-sm text-gray-600">This step will be fully implemented in Plan 2.</p>
                <div class="mt-4 flex justify-between">
                    <button type="button" @click="previousStep" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Previous
                    </button>
                    <button type="button" @click="nextStep" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" x-show="currentStep < 8">
                        Next
                    </button>
                    <button type="button" @click="finish" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" x-show="currentStep === 8">
                        Finish Installation
                    </button>
                </div>
            </div>
        </div>

        <script>
            function setupWizard() {
                return {
                    currentStep: 1,
                    totalSteps: 8,
                    stepLabels: {
                        1: 'Welcome',
                        2: 'System Requirements',
                        3: 'Database Config',
                        4: 'Mail Config',
                        5: 'App Settings',
                        6: 'Admin Account',
                        7: 'Security',
                        8: 'Verify & Finish'
                    },
                    // Step 1
                    appName: 'OpenMail',
                    // Step 3
                    dbHost: '127.0.0.1',
                    dbPort: '3306',
                    dbDatabase: '',
                    dbUsername: '',
                    dbPassword: '',
                    dbTestResult: null,
                    nextStep() {
                        if (this.currentStep < this.totalSteps) {
                            this.currentStep++;
                        }
                    },
                    previousStep() {
                        if (this.currentStep > 1) {
                            this.currentStep--;
                        }
                    },
                    testDatabaseConnection() {
                        // This will be implemented with Livewire in Plan 02
                        this.dbTestResult = { success: false, error: 'Database connection testing will be implemented in Plan 02' };
                    },
                    finish() {
                        // This will be implemented in Plan 02
                        alert('Finish installation will be implemented in Plan 02');
                    }
                }
            }
        </script>
    </div>
    @endverbatim
@endsection