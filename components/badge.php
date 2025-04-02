<?php
function renderBadge($text, $type = 'default', $size = 'md', $dot = false) {
    $types = [
        'default' => 'bg-gray-100 text-gray-800',
        'primary' => 'bg-blue-100 text-blue-800',
        'success' => 'bg-green-100 text-green-800',
        'warning' => 'bg-yellow-100 text-yellow-800',
        'danger' => 'bg-red-100 text-red-800',
        'info' => 'bg-indigo-100 text-indigo-800'
    ];

    $sizes = [
        'sm' => 'text-xs px-2 py-0.5',
        'md' => 'text-sm px-2.5 py-0.5',
        'lg' => 'text-base px-3 py-1'
    ];

    $baseClasses = 'inline-flex items-center rounded-full font-medium';
    $typeClasses = $types[$type] ?? $types['default'];
    $sizeClasses = $sizes[$size] ?? $sizes['md'];

    $dotHtml = $dot ? "<span class=\"w-1.5 h-1.5 mr-1.5 rounded-full bg-current\"></span>" : "";

    return "
        <span class=\"$baseClasses $typeClasses $sizeClasses\">
            $dotHtml
            $text
        </span>
    ";
}

// Usage examples:
// echo renderBadge('Active', 'success', 'md', true);
// echo renderBadge('Pending', 'warning');
// echo renderBadge('Completed', 'primary', 'lg');
// echo renderBadge('Error', 'danger', 'sm', true);
?>