<?php
function renderConsentModal() {
    return '
    <div id="consentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-2xl rounded-xl bg-white animate-modal-fade-in">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-semibold text-gray-900">Exam Consent</h3>
                    <button onclick="document.getElementById(\'consentModal\').classList.add(\'hidden\')" 
                            class="text-gray-400 hover:text-gray-500 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <form id="consentForm" class="space-y-6">
                    <div class="flex items-start space-x-3">
                        <div class="flex items-center h-5">
                            <input type="checkbox" id="cookiesConsent" 
                                class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary transition duration-150">
                        </div>
                        <label for="cookiesConsent" class="text-sm text-gray-700">
                            <span class="font-medium block mb-1">Cookie Usage</span>
                            <span class="text-gray-500">I accept the use of necessary cookies for exam session management.</span>
                        </label>
                    </div>

                    <div class="flex items-start space-x-3">
                        <div class="flex items-center h-5">
                            <input type="checkbox" id="monitoringConsent" 
                                class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary transition duration-150">
                        </div>
                        <label for="monitoringConsent" class="text-sm text-gray-700">
                            <span class="font-medium block mb-1">Monitoring Consent</span>
                            <span class="text-gray-500">I understand and accept that my exam session will be monitored for academic integrity purposes.</span>
                        </label>
                    </div>

                    <div class="bg-gray-50 -mx-5 -mb-5 px-5 py-4 rounded-b-xl">
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="document.getElementById(\'consentModal\').classList.add(\'hidden\')"
                                class="px-4 py-2 bg-white text-gray-700 hover:bg-gray-50 border border-gray-300 rounded-lg shadow-sm text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                                Cancel
                            </button>
                            <button type="submit" id="submitConsent" disabled
                                class="px-4 py-2 bg-primary text-white rounded-lg shadow-sm text-sm font-medium hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                Accept & Start Exam
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-1rem); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-modal-fade-in {
        animation: modalFadeIn 0.3s ease-out;
    }
    </style>
    ';
}
?>