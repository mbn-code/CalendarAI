<?php
function renderAlert($message, $type = 'info', $dismissible = true) {
    $types = [
        'info' => 'bg-blue-100 text-blue-800 border-blue-200',
        'success' => 'bg-green-100 text-green-800 border-green-200',
        'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'error' => 'bg-red-100 text-red-800 border-red-200'
    ];

    $classes = $types[$type] ?? $types['info'];
    $id = 'alert-' . uniqid();

    $dismissButton = $dismissible ? "
        <button 
            onclick=\"document.getElementById('$id').remove()\"
            class=\"ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex h-8 w-8 hover:bg-opacity-25 hover:bg-gray-900\"
        >
            <span class=\"sr-only\">Dismiss</span>
            <svg class=\"w-5 h-5\" fill=\"currentColor\" viewBox=\"0 0 20 20\">
                <path fill-rule=\"evenodd\" d=\"M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z\" clip-rule=\"evenodd\"></path>
            </svg>
        </button>
    " : '';

    return "
        <div id=\"$id\" class=\"flex items-center p-4 mb-4 border rounded-lg $classes\" role=\"alert\">
            <div class=\"ml-3 text-sm font-medium\">
                $message
            </div>
            $dismissButton
        </div>
    ";
}

// Usage example:
// echo renderAlert('Changes saved successfully!', 'success');
// echo renderAlert('Please check your input.', 'error');
// echo renderAlert('Your session will expire soon.', 'warning', false);
?>