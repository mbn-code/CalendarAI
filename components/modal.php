<?php
class Modal {
    private $id;
    private $title;
    private $content;
    private $footer;
    private $size;
    
    public function __construct($id) {
        $this->id = $id;
        $this->size = 'md';
    }
    
    public function setTitle($title) {
        $this->title = $title;
        return $this;
    }
    
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }
    
    public function setFooter($footer) {
        $this->footer = $footer;
        return $this;
    }
    
    public function setSize($size) {
        $this->size = $size;
        return $this;
    }
    
    public function render() {
        $sizes = [
            'sm' => 'max-w-md',
            'md' => 'max-w-lg',
            'lg' => 'max-w-2xl',
            'xl' => 'max-w-4xl'
        ];
        
        $sizeClass = $sizes[$this->size] ?? $sizes['md'];
        
        return "
            <div id=\"{$this->id}\" class=\"fixed inset-0 z-50 hidden overflow-y-auto\" aria-labelledby=\"modal-title\" role=\"dialog\" aria-modal=\"true\">
                <div class=\"flex items-center justify-center min-h-screen p-4\">
                    <div class=\"fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity\"></div>
                    
                    <div class=\"relative bg-white rounded-lg $sizeClass w-full shadow-xl\">
                        <div class=\"absolute top-0 right-0 pt-4 pr-4\">
                            <button type=\"button\" onclick=\"closeModal('{$this->id}')\" class=\"text-gray-400 hover:text-gray-500\">
                                <span class=\"sr-only\">Close</span>
                                <svg class=\"h-6 w-6\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\" />
                                </svg>
                            </button>
                        </div>
                        
                        <div class=\"px-4 pt-5 pb-4 sm:p-6\">
                            " . ($this->title ? "<h3 class=\"text-lg font-medium text-gray-900 mb-4\">{$this->title}</h3>" : "") . "
                            <div class=\"mt-2\">{$this->content}</div>
                        </div>
                        
                        " . ($this->footer ? "<div class=\"px-4 py-3 sm:px-6 bg-gray-50 rounded-b-lg\">{$this->footer}</div>" : "") . "
                    </div>
                </div>
            </div>
            
            <script>
            function showModal(id) {
                document.getElementById(id).classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
            
            function closeModal(id) {
                document.getElementById(id).classList.add('hidden');
                document.body.style.overflow = 'auto';
            }
            </script>
        ";
    }
}

// Usage example:
// $modal = new Modal('warning-modal');
// echo $modal
//     ->setTitle('Warning')
//     ->setContent('<p>Are you sure you want to end this exam session?</p>')
//     ->setFooter('
//         <div class="flex justify-end space-x-3">
//             <button onclick="closeModal(\'warning-modal\')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
//             <button onclick="endExam()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700">End Exam</button>
//         </div>
//     ')
//     ->render();
?>