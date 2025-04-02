<?php
function renderCalendarAssistant() {
    return <<<HTML
    <div id="calendarAssistant" class="fixed right-0 top-0 h-screen w-80 bg-white shadow-lg transform translate-x-full transition-transform duration-300 z-40">
        <!-- Toggle Button -->
        <button id="toggleAssistant" 
                class="absolute -left-12 top-1/2 -translate-y-1/2 bg-purple-500 text-white p-3 rounded-l-lg shadow-md hover:bg-purple-600 transition-colors">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <!-- Chat Interface -->
        <div class="flex flex-col h-full">
            <!-- Header -->
            <div class="p-4 bg-purple-500 text-white">
                <h3 class="text-lg font-semibold">Calendar Assistant</h3>
            </div>
            
            <!-- Chat Messages -->
            <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-4">
                <div class="chat-message-assistant">
                    Hi! I'm your calendar assistant. I can help you:
                    • Add new events
                    • Move existing events
                    • Delete events
                    • Find optimal times for tasks
                    
                    Just tell me what you need!
                </div>
            </div>
            
            <!-- Input Area -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex space-x-2">
                    <input type="text" id="chatInput" 
                           class="flex-1 rounded-lg border-gray-300 shadow-sm" 
                           placeholder="Type your message...">
                    <button id="sendMessage" 
                            class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Processing Indicator -->
        <div id="processingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
        </div>
    </div>
HTML;
}
?>