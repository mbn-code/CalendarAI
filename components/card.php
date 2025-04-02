<?php
class Card {
    private $header = '';
    private $body = '';
    private $footer = '';
    private $classes = '';

    public function setHeader($content) {
        $this->header = "<div class=\"px-6 py-4 border-b border-gray-100\">$content</div>";
        return $this;
    }

    public function setBody($content) {
        $this->body = "<div class=\"p-4\">$content</div>";
        return $this;
    }

    public function setFooter($content) {
        $this->footer = "<div class=\"px-6 py-4 bg-gray-50 border-t border-gray-100\">$content</div>";
        return $this;
    }

    public function addClasses($classes) {
        $this->classes .= ' ' . $classes;
        return $this;
    }

    public function render() {
        $baseClasses = 'bg-white rounded-lg shadow-md transition-all duration-200' . $this->classes;
        return "
            <div class=\"$baseClasses\">
                {$this->header}
                {$this->body}
                {$this->footer}
            </div>
        ";
    }
}

// Usage example:
// $card = new Card();
// echo $card
//     ->setHeader('<h2 class="text-xl font-semibold">Card Title</h2>')
//     ->setBody('<p>Card content goes here</p>')
//     ->setFooter('Footer content')
//     ->addClasses('my-4')
//     ->render();
?>