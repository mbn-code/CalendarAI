<?php
function renderTimer($id, $startTime = null, $options = []) {
    $defaultOptions = [
        'showHours' => true,
        'size' => 'medium',
        'textColor' => 'text-gray-700',
        'warningThreshold' => 300, // 5 minutes in seconds
        'criticalThreshold' => 60  // 1 minute in seconds
    ];
    
    $options = array_merge($defaultOptions, $options);
    
    $sizeClasses = [
        'small' => 'text-sm',
        'medium' => 'text-lg',
        'large' => 'text-2xl'
    ];
    
    $size = $sizeClasses[$options['size']] ?? $sizeClasses['medium'];
    
    $startTimeJs = $startTime ? "'" . $startTime . "'" : 'new Date()';
    
    return "
        <div id=\"timer-$id\" class=\"font-mono $size {$options['textColor']}\">00:00:00</div>
        <script>
        (function() {
            const timerElement = document.getElementById('timer-$id');
            const startTime = new Date($startTimeJs);
            const warningThreshold = {$options['warningThreshold']};
            const criticalThreshold = {$options['criticalThreshold']};
            
            function updateTimer() {
                const now = new Date();
                const diff = Math.floor((now - startTime) / 1000);
                
                const hours = Math.floor(diff / 3600);
                const minutes = Math.floor((diff % 3600) / 60);
                const seconds = diff % 60;
                
                const time = `\${String(hours).padStart(2, '0')}:\${String(minutes).padStart(2, '0')}:\${String(seconds).padStart(2, '0')}`;
                timerElement.textContent = time;
                
                if (diff <= criticalThreshold) {
                    timerElement.classList.add('text-red-600', 'animate-pulse');
                } else if (diff <= warningThreshold) {
                    timerElement.classList.add('text-yellow-600');
                }
            }
            
            updateTimer();
            setInterval(updateTimer, 1000);
        })();
        </script>
    ";
}

// Usage example:
// echo renderTimer('exam-1', '2023-10-10 14:00:00', ['size' => 'large']);
// echo renderTimer('exam-2', null, ['showHours' => false, 'textColor' => 'text-blue-600']);
?>