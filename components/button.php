<?php
function renderButton($text, $type = 'primary', $attributes = []) {
    $baseClasses = 'px-4 py-2 rounded-md transition-all duration-200 hover-shadow font-medium text-sm';
    
    $variants = [
        'primary' => 'bg-primary hover:bg-blue-600 text-white',
        'secondary' => 'bg-gray-100 hover:bg-gray-200 text-gray-700',
        'danger' => 'bg-red-500 hover:bg-red-600 text-white',
        'ghost' => 'hover:bg-gray-100 text-gray-700'
    ];
    
    $classes = $baseClasses . ' ' . ($variants[$type] ?? $variants['primary']);
    
    if (isset($attributes['class'])) {
        $classes .= ' ' . $attributes['class'];
        unset($attributes['class']);
    }
    
    $attrs = '';
    foreach ($attributes as $key => $value) {
        $attrs .= " $key=\"$value\"";
    }
    
    return "<button class=\"$classes\"$attrs>$text</button>";
}

// Usage example:
// echo renderButton('Save Changes');
// echo renderButton('Cancel', 'secondary', ['onclick' => 'closeModal()']);
// echo renderButton('Delete', 'danger', ['data-id' => '123']);
?>