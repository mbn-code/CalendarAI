<?php
// default footer for all pages

function renderFooter() {
    $year = date('Y');
    return "
        <footer class='bg-white border-t border-gray-100 mt-auto'>
            <div class='container mx-auto px-6 py-4'>
                <div class='flex justify-between items-center'>
                    <div class='text-sm text-gray-600'>
                        © $year Exam Monitor. All rights reserved.
                    </div>
                    <div class='space-x-4'>
                        <a href='#' class='text-sm text-gray-600 hover:text-primary transition-colors'>Privacy Policy</a>
                        <a href='#' class='text-sm text-gray-600 hover:text-primary transition-colors'>Terms of Service</a>
                        <a href='#' class='text-sm text-gray-600 hover:text-primary transition-colors'>Support</a>
                    </div>
                </div>
            </div>
        </footer>
        </body>
        </html>
    ";
}
?>